<?php

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Tester\CommandTester;
use Venusian\Installer\Console\Commands\InstallExtensionCommand;
use Venusian\Installer\Console\Commands\InstallToolkitExtensionCommand;
use Venusian\Installer\Tests\Fakes\FakeHost;
use Venusian\Installer\Tests\Fakes\FakeProcessRunner;
use Venusian\Installer\Workflows\Extensions\ExtensionsFlow;

/*
 * install:ext (the core extensions) and install:toolkit-ext (the ones
 * Topside's toolkits run on) over a described machine.
 */

/**
 * @param  list<array{string, int, string}>  $rules  [text in the command line, exit code, stdout]; first match wins.
 */
function catalogRunner(array $rules = []): FakeProcessRunner
{
    $rules = [...$rules, ['--version', 0, '🥧 PHP Installer for Extensions (PIE) 1.5.1'], ['/opt/php/bin/php-config --php-binary', 0, '/opt/php/bin/php'], ['--php-binary', 127, '']];

    return new FakeProcessRunner(function (array $command) use ($rules): array {
        foreach ($rules as [$text, $exit, $output]) {
            if (str_contains(implode(' ', $command), $text)) {
                return [$exit, $output];
            }
        }

        return [0, ''];
    });
}

/**
 * @param  class-string<InstallExtensionCommand>  $command
 * @param  list<string>  $keys
 * @return array{int, FakeProcessRunner}
 */
function installExt(FakeHost $host, array $keys = [], ?string $ext = null, bool $interactive = true, ?FakeProcessRunner $runner = null, string $command = InstallExtensionCommand::class): array
{
    Prompt::fake($keys);
    $runner ??= catalogRunner();
    $flow = new ExtensionsFlow($host, $runner, $command::catalog(), ask: false, preselect: false);
    $tester = new CommandTester(new $command($flow));

    return [$tester->execute(is_null($ext) ? [] : ['ext' => $ext], ['interactive' => $interactive]), $runner];
}

function installs(FakeProcessRunner $runner): array
{
    return array_values(array_map(fn (array $command): string => $command[3], array_filter($runner->commands, fn (array $command): bool => ($command[2] ?? null) === 'install')));
}

it('lists epoll, kqueue and multicurl with nothing selected, and marks what cannot be installed here', function () {
    $host = new FakeHost(os: 'Darwin', loaded: ['kqueue'], executables: ['pie' => '/usr/local/bin/pie'], unreleased: ['php-io-extensions/multicurl']);

    [$exit, $runner] = installExt($host, [Key::ENTER]);

    $frame = Prompt::strippedContent();

    expect($frame)->toContain('– epoll — event loop waiting on Linux (Linux only)')
        ->and($frame)->toContain('– kqueue — event loop waiting on macOS (installed)')
        ->and($frame)->toContain('◻ multicurl — HTTP requests that run on the event loop [0.11.x-dev]')
        ->and($frame)->not->toContain('appkit')
        ->and($frame)->not->toContain('Install first-party PHP extensions?')
        ->and($frame)->toContain('None selected.')
        ->and(installs($runner))->toBe([])
        ->and($exit)->toBe(0);
});

it('lists appkit, gtk, qt and raven from install:toolkit-ext, appkit for macOS only', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['qt'], executables: ['pie' => '/usr/local/bin/pie'], absent: ['php-io-extensions/raven']);

    [$exit, $runner] = installExt($host, [Key::SPACE, Key::ENTER], command: InstallToolkitExtensionCommand::class);

    $frame = Prompt::strippedContent();

    expect($frame)->toContain('– appkit — native macOS windows and controls (macOS only)')
        ->and($frame)->toContain('gtk — GTK 4 windows and controls')
        ->and($frame)->toContain('– qt — Qt 6 windows and controls (installed)')
        ->and($frame)->toContain('– raven — Raven windows and controls (not on Packagist at 0.11)')
        ->and($frame)->not->toContain('epoll')
        ->and(installs($runner))->toBe(['php-io-extensions/gtk:^0.11'])
        ->and($exit)->toBe(0);
});

it('offers appkit from install:toolkit-ext on macOS', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie']);

    [$exit, $runner] = installExt($host, ext: 'appkit', command: InstallToolkitExtensionCommand::class);

    expect(installs($runner))->toBe(['php-io-extensions/appkit:^0.11'])
        ->and($exit)->toBe(0);
});

it('installs the rows selected from the list', function () {
    $host = new FakeHost(os: 'Linux', executables: ['pie' => '/usr/local/bin/pie']);

    // Opens on epoll (kqueue is disabled on Linux). Select it, then End for multicurl.
    [$exit, $runner] = installExt($host, [Key::SPACE, Key::END[0], Key::SPACE, Key::ENTER]);

    expect(installs($runner))->toBe(['php-io-extensions/epoll:^0.11', 'php-io-extensions/multicurl:^0.11'])
        ->and(Prompt::strippedContent())->toContain('Extensions Installed')
        ->and($exit)->toBe(0);
});

it('installs a named extension without the list, interactive or not', function (bool $interactive) {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie']);

    [$exit, $runner] = installExt($host, ext: 'multicurl', interactive: $interactive);

    expect(installs($runner))->toBe(['php-io-extensions/multicurl:^0.11'])
        ->and(Prompt::strippedContent())->not->toContain('Extensions to install')
        ->and(Prompt::strippedContent())->toContain('Extensions Installed')
        ->and($exit)->toBe(0);
})->with(['interactive' => [true], 'non-interactive' => [false]]);

it('ends with the reason when the named extension cannot be installed', function (string $ext, array $host, string $note, int $code, string $command) {
    [$exit, $runner] = installExt(new FakeHost(...$host, executables: ['pie' => '/usr/local/bin/pie']), ext: $ext, command: $command);

    expect(Prompt::strippedContent())->toContain($note)
        ->and($runner->commands)->toBe([])
        ->and($exit)->toBe($code);
})->with([
    'another operating system' => ['appkit', ['os' => 'Linux'], 'Nothing to install: appkit (macOS only).', 0, InstallToolkitExtensionCommand::class],
    'already loaded' => ['multicurl', ['loaded' => ['multicurl']], 'Nothing to install: multicurl (installed).', 0, InstallExtensionCommand::class],
    'not on Packagist at 0.11' => ['raven', ['absent' => ['php-io-extensions/raven']], 'not on Packagist at 0.11', 0, InstallToolkitExtensionCommand::class],
    'not in the core catalog' => ['qt', [], 'Unknown extension [qt]. Choose one of: epoll, kqueue, multicurl.', 1, InstallExtensionCommand::class],
    'not in the topside catalog' => ['epoll', [], 'Unknown extension [epoll]. Choose one of: appkit, gtk, qt, raven.', 1, InstallToolkitExtensionCommand::class],
]);

it('installs the 0.11 development branch of an extension with no 0.11 tag', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie'], unreleased: ['php-io-extensions/raven']);

    [$exit, $runner] = installExt($host, ext: 'raven', command: InstallToolkitExtensionCommand::class);

    expect(installs($runner))->toBe(['php-io-extensions/raven:0.11.x-dev'])
        ->and(Prompt::strippedContent())->toContain('installed (0.11.x-dev)')
        ->and(Prompt::strippedContent())->toContain('Extensions Installed')
        ->and($exit)->toBe(0);
});

it('prefers the tagged release when both it and the development branch exist', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie']);

    [, $runner] = installExt($host, ext: 'kqueue');

    expect(installs($runner))->toBe(['php-io-extensions/kqueue:^0.11']);
});

it('leaves an extension installable when Packagist cannot be asked', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie'], packagist_answers: false);

    [$exit, $runner] = installExt($host, ext: 'qt', command: InstallToolkitExtensionCommand::class);

    expect(installs($runner))->toBe(['php-io-extensions/qt:^0.11'])
        ->and($exit)->toBe(0);
});

it('asks for a name on a non-interactive run without one', function () {
    [$exit, $runner] = installExt(new FakeHost(executables: ['pie' => '/usr/local/bin/pie']), interactive: false);

    expect(Prompt::strippedContent())->toContain('Name the extension to install on a non-interactive run: epoll, kqueue, multicurl.')
        ->and(installs($runner))->toBe([])
        ->and($exit)->toBe(1);
});

it('does not download PIE on a non-interactive run', function () {
    $host = new FakeHost(os: 'Darwin');

    [$exit] = installExt($host, ext: 'multicurl', interactive: false);

    expect(Prompt::strippedContent())->toContain('PIE is not installed. Run this command interactively to install it.')
        ->and($host->downloads)->toBe([])
        ->and($exit)->toBe(1);
});

it('exits 1 and says so when an extension fails to build', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie']);

    [$exit] = installExt($host, ext: 'qt', runner: catalogRunner([['install php-io-extensions/qt', 1, '']]), command: InstallToolkitExtensionCommand::class);

    expect(Prompt::strippedContent())->toContain('Extensions Not All Installed')
        ->and(Prompt::strippedContent())->toContain('failed (PIE exit code 1)')
        ->and($exit)->toBe(1);
});

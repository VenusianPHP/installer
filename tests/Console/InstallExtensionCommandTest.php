<?php

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Tester\CommandTester;
use Venusian\Installer\Console\Commands\InstallExtensionCommand;
use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Tests\Fakes\FakeHost;
use Venusian\Installer\Tests\Fakes\FakeProcessRunner;
use Venusian\Installer\Workflows\Extensions\ExtensionsFlow;

/*
 * install:ext over a described machine, with the whole catalog on offer.
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
 * @param  list<string>  $keys
 * @return array{int, FakeProcessRunner}
 */
function installExt(FakeHost $host, array $keys = [], ?string $ext = null, bool $interactive = true, ?FakeProcessRunner $runner = null): array
{
    Prompt::fake($keys);
    $runner ??= catalogRunner();
    $flow = new ExtensionsFlow($host, $runner, FirstPartyExtension::cases(), ask: false, preselect: false);
    $tester = new CommandTester(new InstallExtensionCommand($flow));

    return [$tester->execute(is_null($ext) ? [] : ['ext' => $ext], ['interactive' => $interactive]), $runner];
}

function installs(FakeProcessRunner $runner): array
{
    return array_values(array_map(fn (array $command): string => $command[3], array_filter($runner->commands, fn (array $command): bool => ($command[2] ?? null) === 'install')));
}

it('lists the whole catalog with nothing selected, and marks what cannot be installed here', function () {
    $host = new FakeHost(os: 'Darwin', loaded: ['kqueue', 'pcurl'], executables: ['pie' => '/usr/local/bin/pie'], unreleased: ['php-io-extensions/gtk'], absent: ['php-io-extensions/qt']);

    [$exit, $runner] = installExt($host, [Key::ENTER]);

    $frame = Prompt::strippedContent();

    expect($frame)->toContain('– epoll — event loop waiting on Linux (Linux only)')
        ->and($frame)->toContain('– kqueue — event loop waiting on macOS (installed)')
        ->and($frame)->toContain('– pcurl — HTTP requests that run on the event loop (installed)')
        ->and($frame)->toContain('› ◻ appkit — native macOS windows and controls')
        ->and($frame)->toContain('◻ gtk — GTK 4 windows and controls [0.10.x-dev]')
        ->and($frame)->toContain('– qt — Qt 6 windows and controls (not on Packagist at 0.10)')
        ->and($frame)->toContain('◻ fb — framebuffers in C')
        ->and($frame)->toContain('◻ rasterize — shape and text rasterising in C')
        ->and($frame)->toContain('◻ imgdec — PNG, JPEG and TIFF decoding in C')
        ->and($frame)->toContain('◻ posi — POSIX files, terminals and devices')
        ->and($frame)->toContain('◻ ftdi — FTDI USB adapters: GPIO, I2C, SPI, UART')
        ->and($frame)->not->toContain('Install first-party PHP extensions?')
        ->and($frame)->toContain('None selected.')
        ->and(installs($runner))->toBe([])
        ->and($exit)->toBe(0);
});

it('installs the rows selected from the list', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['epoll', 'pcurl'], executables: ['pie' => '/usr/local/bin/pie']);

    // Opens on gtk (epoll, kqueue, pcurl, appkit are disabled on this machine). Select gtk, then End for ftdi.
    [$exit, $runner] = installExt($host, [Key::SPACE, Key::END[0], Key::SPACE, Key::ENTER]);

    expect(installs($runner))->toBe(['php-io-extensions/gtk:^0.10', 'php-io-extensions/ftdi:^0.10'])
        ->and(Prompt::strippedContent())->toContain('Extensions Installed')
        ->and($exit)->toBe(0);
});

it('installs a named extension without the list, interactive or not', function (bool $interactive) {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie']);

    [$exit, $runner] = installExt($host, ext: 'imgdec', interactive: $interactive);

    expect(installs($runner))->toBe(['php-io-extensions/imgdec:^0.10'])
        ->and(Prompt::strippedContent())->not->toContain('Extensions to install')
        ->and(Prompt::strippedContent())->toContain('Extensions Installed')
        ->and($exit)->toBe(0);
})->with(['interactive' => [true], 'non-interactive' => [false]]);

it('ends with the reason when the named extension cannot be installed', function (string $ext, array $host, string $note, int $code) {
    [$exit, $runner] = installExt(new FakeHost(...$host, executables: ['pie' => '/usr/local/bin/pie']), ext: $ext);

    expect(Prompt::strippedContent())->toContain($note)
        ->and($runner->commands)->toBe([])
        ->and($exit)->toBe($code);
})->with([
    'another operating system' => ['appkit', ['os' => 'Linux'], 'Nothing to install: appkit (macOS only).', 0],
    'already loaded' => ['posi', ['loaded' => ['posi']], 'Nothing to install: posi (installed).', 0],
    'not on Packagist at 0.10' => ['qt', ['absent' => ['php-io-extensions/qt']], 'not on Packagist at 0.10', 0],
    'not in the catalog' => ['gd', [], 'Unknown extension [gd]. Choose one of: epoll, kqueue, pcurl, appkit, gtk, qt, fb, rasterize, imgdec, posi, ftdi.', 1],
]);

it('installs the 0.10 development branch of an extension with no 0.10 tag', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie'], unreleased: ['php-io-extensions/imgdec']);

    [$exit, $runner] = installExt($host, ext: 'imgdec');

    expect(installs($runner))->toBe(['php-io-extensions/imgdec:0.10.x-dev'])
        ->and(Prompt::strippedContent())->toContain('installed (0.10.x-dev)')
        ->and(Prompt::strippedContent())->toContain('Extensions Installed')
        ->and($exit)->toBe(0);
});

it('prefers the tagged release when both it and the development branch exist', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie']);

    [, $runner] = installExt($host, ext: 'posi');

    expect(installs($runner))->toBe(['php-io-extensions/posi:^0.10']);
});

it('leaves an extension installable when Packagist cannot be asked', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie'], packagist_answers: false);

    [$exit, $runner] = installExt($host, ext: 'fb');

    expect(installs($runner))->toBe(['php-io-extensions/fb:^0.10'])
        ->and($exit)->toBe(0);
});

it('asks for a name on a non-interactive run without one', function () {
    [$exit, $runner] = installExt(new FakeHost(executables: ['pie' => '/usr/local/bin/pie']), interactive: false);

    expect(Prompt::strippedContent())->toContain('Name the extension to install on a non-interactive run: epoll, kqueue, pcurl, appkit, gtk, qt, fb, rasterize, imgdec, posi, ftdi.')
        ->and(installs($runner))->toBe([])
        ->and($exit)->toBe(1);
});

it('does not download PIE on a non-interactive run', function () {
    $host = new FakeHost(os: 'Darwin');

    [$exit] = installExt($host, ext: 'fb', interactive: false);

    expect(Prompt::strippedContent())->toContain('PIE is not installed. Run this command interactively to install it.')
        ->and($host->downloads)->toBe([])
        ->and($exit)->toBe(1);
});

it('exits 1 and says so when an extension fails to build', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => '/usr/local/bin/pie']);

    [$exit] = installExt($host, ext: 'qt', runner: catalogRunner([['install php-io-extensions/qt', 1, '']]));

    expect(Prompt::strippedContent())->toContain('Extensions Not All Installed')
        ->and(Prompt::strippedContent())->toContain('failed (PIE exit code 1)')
        ->and($exit)->toBe(1);
});

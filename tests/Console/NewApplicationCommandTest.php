<?php

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Venusian\Installer\Console\Commands\NewApplicationCommand;
use Venusian\Installer\Tests\Fakes\FakeHost;
use Venusian\Installer\Tests\Fakes\FakeProcessRunner;

/*
 * `new` end to end. The first tests point PATH at a directory that holds a
 * stand-in `composer` (a script that records its arguments and exits) or
 * nothing. The rest describe the machine with FakeHost and answer each
 * command with FakeProcessRunner. No network, no real composer.
 */

beforeEach(function () {
    $this->previousPath = getenv('PATH');
    $this->bin = sys_get_temp_dir().'/venusian-installer-'.bin2hex(random_bytes(6));
    (new Filesystem)->mkdir($this->bin);
    putenv("PATH={$this->bin}");
    Prompt::fake();
});

afterEach(function () {
    putenv("PATH={$this->previousPath}");
    (new Filesystem)->remove($this->bin);
});

function standInComposer(string $bin, int $exit): void
{
    $filesystem = new Filesystem;
    $filesystem->dumpFile("{$bin}/composer", "#!/bin/sh\necho \"\$@\" >> \"{$bin}/composer.calls\"\nexit {$exit}\n");
    $filesystem->chmod("{$bin}/composer", 0755);
}

it('fails, and exits 1, without running create-project when composer is not on PATH', function () {
    $tester = new CommandTester(new NewApplicationCommand);

    $exit = $tester->execute(['name' => "{$this->bin}/app"], ['interactive' => false]);

    expect($exit)->toBe(1)
        ->and(Prompt::strippedContent())->toContain('Installation Failed')
        ->and(Prompt::strippedContent())->toContain('Could not find the composer executable.');
});

it('creates the application and reports that the optional steps are not offered on a non-interactive run', function () {
    standInComposer($this->bin, 0);
    $tester = new CommandTester(new NewApplicationCommand);

    $exit = $tester->execute(['name' => "{$this->bin}/app"], ['interactive' => false]);

    expect($exit)->toBe(0)
        ->and(trim((string) file_get_contents("{$this->bin}/composer.calls")))->toBe("create-project venusian/venusian:^0.11.1 {$this->bin}/app --remove-vcs --prefer-dist")
        ->and(Prompt::strippedContent())->toContain('Installation Successful!')
        ->and(Prompt::strippedContent())->toContain('Application ready at')
        ->and(Prompt::strippedContent())->toMatch('/PHP extensions: (Not offered: this run is not interactive\.|Nothing to install: )/')
        ->and(Prompt::strippedContent())->toContain('Topside: Not offered: this run is not interactive.')
        ->and(Prompt::strippedContent())->not->toContain('Toolkit SDKs');
});

it('runs a PHP composer under the PHP binary running the installer, not the one its shebang finds', function () {
    // A shebang naming a php that does not exist: only PHP_BINARY can run it.
    (new Filesystem)->dumpFile("{$this->bin}/composer", "#!/nonexistent/php\n<?php file_put_contents(".var_export("{$this->bin}/composer.calls", true).", PHP_BINARY);\n");
    (new Filesystem)->chmod("{$this->bin}/composer", 0755);
    $tester = new CommandTester(new NewApplicationCommand);

    $exit = $tester->execute(['name' => "{$this->bin}/app"], ['interactive' => false]);

    expect($exit)->toBe(0)
        ->and(file_get_contents("{$this->bin}/composer.calls"))->toBe(PHP_BINARY);
});

it('exits 1 and does not reach the extension step when create-project fails', function () {
    standInComposer($this->bin, 3);
    $tester = new CommandTester(new NewApplicationCommand);

    $exit = $tester->execute(['name' => "{$this->bin}/app"], ['interactive' => false]);

    expect($exit)->toBe(1)
        ->and(Prompt::strippedContent())->toContain('Installation Failed')
        ->and(Prompt::strippedContent())->not->toContain('PHP extensions');
});

const APP = '/work/app';
const NEW_PHP = '/opt/php/bin/php';
const NEW_COMPOSER = '/usr/bin/composer';
const NEW_PIE = '/usr/local/bin/pie';

/**
 * A machine with composer and PIE whose PHP already loads the core
 * extensions it can have, so `new` goes straight from the application to
 * Topside. Extensions in $loads_after load once PIE has installed them.
 *
 * @param  list<array{string, int, string}>  $rules  [text in the command line, exit code, stdout]; first match wins.
 * @param  list<string>  $loads_after
 */
function newRunner(array $rules = [], array $loads_after = []): FakeProcessRunner
{
    $installed = [];
    $rules = [...$rules, ['--version', 0, '🥧 PHP Installer for Extensions (PIE) 1.5.1'], ['/opt/php/bin/php-config --php-binary', 0, NEW_PHP], ['--php-binary', 127, '']];

    return new FakeProcessRunner(function (array $command) use ($rules, $loads_after, &$installed): array {
        $line = implode(' ', $command);

        foreach ($rules as [$text, $exit, $output]) {
            if (str_contains($line, $text)) {
                return [$exit, $output];
            }
        }

        if (($command[2] ?? null) === 'install' && $command[1] === NEW_PIE) {
            $installed[] = explode(':', explode('/', $command[3])[1])[0];

            return [0, ''];
        }

        if (($command[1] ?? null) === '--ri') {
            return [in_array($command[2], $loads_after, true) && in_array($command[2], $installed, true) ? 0 : 1, ''];
        }

        return [0, ''];
    });
}

/**
 * @param  list<string>  $keys
 * @return array{int, FakeProcessRunner}
 */
function newApp(FakeHost $host, FakeProcessRunner $runner, array $keys): array
{
    Prompt::fake($keys);
    $tester = new CommandTester(new NewApplicationCommand($host, $runner));

    return [$tester->execute(['name' => APP], ['interactive' => true]), $runner];
}

function macHost(array $loaded = ['kqueue', 'multicurl']): FakeHost
{
    return new FakeHost(os: 'Darwin', loaded: $loaded, executables: ['composer' => NEW_COMPOSER, 'pie' => NEW_PIE]);
}

it('requires venusian/topside and publishes its config when accepted, then stops when toolkit SDKs are declined', function () {
    // Topside: yes. Toolkit SDKs: no.
    [$exit, $runner] = newApp(macHost(), newRunner(), [Key::ENTER, 'n', Key::ENTER]);

    expect($runner->commands)->toContain([NEW_PHP, NEW_COMPOSER, 'require', 'venusian/topside:^0.11', '--no-interaction'])
        ->and($runner->commands)->toContain([NEW_PHP, 'computer', 'vendor:publish', '--tag=surface-config', '--no-interaction'])
        ->and(Prompt::strippedContent())->toContain('Topside: installed, config published')
        ->and(Prompt::strippedContent())->toContain('Toolkit SDKs: Declined.')
        ->and($exit)->toBe(0);
});

it('asks nothing about toolkit SDKs when Topside is declined', function () {
    [$exit, $runner] = newApp(macHost(), newRunner(), ['n', Key::ENTER]);

    expect(array_filter($runner->commands, fn (array $command): bool => in_array('require', $command, true)))->toBe([])
        ->and(Prompt::strippedContent())->toContain('Topside: Declined.')
        ->and(Prompt::strippedContent())->not->toContain('Install toolkit SDKs?')
        ->and($exit)->toBe(0);
});

it('ends the Topside step, and asks nothing more, when composer require fails', function () {
    [$exit] = newApp(macHost(), newRunner([['require venusian/topside', 2, '']]), [Key::ENTER]);

    expect(Prompt::strippedContent())->toMatch('/Topside: composer require venusian\/topside failed \(exit code\s*│\s*│\s*2\)\./')
        ->and(Prompt::strippedContent())->not->toContain('Install toolkit SDKs?')
        ->and($exit)->toBe(0);
});

it('says so when Topside is installed but its config is not published', function () {
    newApp(macHost(), newRunner([['vendor:publish', 1, '']]), [Key::ENTER, 'n', Key::ENTER]);

    expect(Prompt::strippedContent())->toContain('Topside: installed, but publishing its config failed');
});

it('lists the four toolkits with appkit disabled off macOS, nothing selected, and accepts none', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['epoll', 'multicurl'], executables: ['composer' => NEW_COMPOSER, 'pie' => NEW_PIE]);

    // Topside: yes. Toolkit SDKs: yes. The list: Enter with nothing selected.
    [$exit, $runner] = newApp($host, newRunner(), [Key::ENTER, Key::ENTER, Key::ENTER]);

    $frame = Prompt::strippedContent();

    expect($frame)->toContain('– appkit — native macOS windows and controls (macOS only)')
        ->and($frame)->toContain('◻ gtk — GTK 4 windows and controls')
        ->and($frame)->toContain('◻ qt — Qt 6 windows and controls')
        ->and($frame)->toContain('◻ raven — Raven windows and controls')
        ->and($frame)->toContain('Toolkit SDKs: None selected.')
        ->and(array_filter($runner->commands, fn (array $command): bool => ($command[2] ?? null) === 'install'))->toBe([])
        ->and($exit)->toBe(0);
});

it('installs each selected toolkit\'s extension, then its adapter', function () {
    // Topside: yes. Toolkit SDKs: yes. Select appkit (first row), Down, Down to qt, select, Enter.
    [$exit, $runner] = newApp(macHost(), newRunner(loads_after: ['appkit', 'qt']), [Key::ENTER, Key::ENTER, Key::SPACE, Key::DOWN, Key::DOWN, Key::SPACE, Key::ENTER]);

    $pie = array_values(array_filter($runner->commands, fn (array $command): bool => ($command[2] ?? null) === 'install'));
    $adapters = array_values(array_filter($runner->commands, fn (array $command): bool => str_starts_with($command[3] ?? '', 'jovian/')));

    expect($pie)->toBe([
        [NEW_PHP, NEW_PIE, 'install', 'php-io-extensions/appkit:^0.11', '--with-php-config=/opt/php/bin/php-config'],
        [NEW_PHP, NEW_PIE, 'install', 'php-io-extensions/qt:^0.11', '--with-php-config=/opt/php/bin/php-config'],
    ])
        ->and($adapters)->toBe([
            [NEW_PHP, NEW_COMPOSER, 'require', 'jovian/venusian-appkit:^0.11', '--no-interaction'],
            [NEW_PHP, NEW_COMPOSER, 'require', 'jovian/venusian-qt:^0.11', '--no-interaction'],
        ])
        ->and(Prompt::strippedContent())->toContain('Toolkit SDKs:')
        ->and($exit)->toBe(0);
});

it('installs ext-appkit with ext-gtk for GTK on macOS, and leaves an extension already loaded alone', function () {
    // qt already loads. Select gtk (second row) and qt (third).
    [, $runner] = newApp(macHost(['kqueue', 'multicurl', 'qt']), newRunner([['--ri qt', 0, '']], loads_after: ['gtk', 'appkit']), [Key::ENTER, Key::ENTER, Key::DOWN, Key::SPACE, Key::DOWN, Key::SPACE, Key::ENTER]);

    $pie = array_values(array_map(fn (array $command): string => $command[3], array_filter($runner->commands, fn (array $command): bool => ($command[2] ?? null) === 'install')));
    $adapters = array_values(array_map(fn (array $command): string => $command[3], array_filter($runner->commands, fn (array $command): bool => str_starts_with($command[3] ?? '', 'jovian/'))));

    expect($pie)->toBe(['php-io-extensions/gtk:^0.11', 'php-io-extensions/appkit:^0.11'])
        ->and($adapters)->toBe(['jovian/venusian-gtk:^0.11', 'jovian/venusian-qt:^0.11']);
});

it('does not require an adapter whose extension failed to build, and still installs the others', function () {
    $runner = newRunner([['install php-io-extensions/raven', 1, '']], loads_after: ['qt']);

    // Select qt (third row) and raven (fourth).
    [$exit] = newApp(macHost(), $runner, [Key::ENTER, Key::ENTER, Key::DOWN, Key::DOWN, Key::SPACE, Key::DOWN, Key::SPACE, Key::ENTER]);

    $adapters = array_values(array_map(fn (array $command): string => $command[3], array_filter($runner->commands, fn (array $command): bool => str_starts_with($command[3] ?? '', 'jovian/'))));

    expect($adapters)->toBe(['jovian/venusian-qt:^0.11'])
        ->and(Prompt::strippedContent())->toContain('not installed: ext-raven (failed (PIE exit code 1))')
        ->and($exit)->toBe(0);
});

it('reports an adapter composer could not require', function () {
    $runner = newRunner([['require jovian/venusian-raven', 2, '']], loads_after: ['raven']);

    newApp(macHost(), $runner, [Key::ENTER, Key::ENTER, Key::UP, Key::SPACE, Key::ENTER]);

    expect(Prompt::strippedContent())->toContain('raven  not installed: composer require jovian/venusian-raven');
});

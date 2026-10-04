<?php

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Venusian\Installer\Tests\Fakes\FakeHost;
use Venusian\Installer\Tests\Fakes\FakeProcessRunner;
use Venusian\Installer\Workflows\Extensions\ExtensionsFlow;

/*
 * The whole extension step over a described machine: FakeHost says what is
 * there, FakeProcessRunner answers each command, Prompt::fake() presses keys.
 */

const PHP = '/opt/php/bin/php';
const PIE = '/usr/local/bin/pie';
const PHP_CONFIG = '/opt/php/bin/php-config';
const VERSION_LINE = '🥧 PHP Installer for Extensions (PIE) 1.4.4';

/**
 * Answers the first rule whose text the command line contains, else exit 0
 * with no output. The defaults describe a working machine.
 *
 * @param  list<array{string, int, string}>  $rules  [text, exit code, stdout]
 */
function runner(array $rules = []): FakeProcessRunner
{
    $rules = [...$rules, ['--version', 0, VERSION_LINE], [PHP_CONFIG.' --php-binary', 0, PHP], ['--php-binary', 127, '']];

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
 * @return array<string, mixed> The shared bag after the flow.
 */
function runExtensions(FakeHost $host, FakeProcessRunner $runner, array $keys = [], bool $interactive = true): array
{
    Prompt::fake($keys);
    $shared = ['interactive' => $interactive, 'output_callback' => null];
    (new ExtensionsFlow($host, $runner))->run($shared);

    return $shared;
}

it('offers nothing on a non-interactive run', function () {
    $runner = runner();

    $shared = runExtensions(new FakeHost(executables: ['pie' => PIE]), $runner, interactive: false);

    expect($shared['extensions_note'])->toBe('Not offered: this run is not interactive.')
        ->and($runner->commands)->toBe([])
        ->and($shared)->not->toHaveKey('extension_results');
});

it('offers nothing when every extension is installed or belongs to another operating system', function () {
    $runner = runner();

    $shared = runExtensions(new FakeHost(os: 'Darwin', loaded: ['kqueue', 'pcurl']), $runner);

    expect($shared['extensions_note'])->toBe('Nothing to install: epoll (Linux only), kqueue (installed), pcurl (installed).')
        ->and($shared['extension_states'])->toBe([
            'php-io-extensions/epoll:^0.10' => 'Linux only',
            'php-io-extensions/kqueue:^0.10' => 'installed',
            'php-io-extensions/pcurl:^0.10' => 'installed',
        ])
        ->and($runner->commands)->toBe([]);
});

it('stops when the offer is declined', function () {
    $runner = runner();

    $shared = runExtensions(new FakeHost(executables: ['pie' => PIE]), $runner, ['n', Key::ENTER]);

    expect($shared['extensions_note'])->toBe('Declined.')
        ->and($runner->commands)->toBe([]);
});

it('installs every selected extension through PIE under the running PHP binary', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => PIE]);
    $runner = runner();

    // Enter accepts the offer; Enter accepts the list, which starts with every installable row selected.
    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($runner->commands)->toBe([
        [PHP_CONFIG, '--php-binary'],
        [PHP, PIE, '--version'],
        [PHP, PIE, 'install', 'php-io-extensions/kqueue:^0.10', '--with-php-config='.PHP_CONFIG],
        [PHP, '--ri', 'kqueue'],
        [PHP, PIE, 'install', 'php-io-extensions/pcurl:^0.10', '--with-php-config='.PHP_CONFIG],
        [PHP, '--ri', 'pcurl'],
    ])
        ->and($runner->on_terminal)->toBe([
            [PHP, PIE, 'install', 'php-io-extensions/kqueue:^0.10', '--with-php-config='.PHP_CONFIG],
            [PHP, PIE, 'install', 'php-io-extensions/pcurl:^0.10', '--with-php-config='.PHP_CONFIG],
        ])
        ->and($shared['pie_binary'])->toBe(PIE)
        ->and($shared['php_config'])->toBe(PHP_CONFIG)
        ->and($shared['extensions_selected'])->toBe(['php-io-extensions/kqueue:^0.10', 'php-io-extensions/pcurl:^0.10'])
        ->and($shared['extension_results'])->toBe(['kqueue' => 'installed', 'pcurl' => 'installed'])
        ->and($shared)->not->toHaveKey('extensions_note');
});

it('shows the other operating system\'s extension and an installed one as disabled rows', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['pcurl'], executables: ['pie' => PIE]);

    $shared = runExtensions($host, runner(), [Key::ENTER, Key::ENTER]);

    expect(Prompt::strippedContent())->toContain('◼ epoll — event loop waiting on Linux')
        ->and(Prompt::strippedContent())->toContain('– kqueue — event loop waiting on macOS (macOS only)')
        ->and(Prompt::strippedContent())->toContain('– pcurl — HTTP requests that run on the event loop (installed)')
        ->and($shared['extension_results'])->toBe(['epoll' => 'installed']);
});

it('looks for PIE on PATH and in the directory it installs PIE into', function () {
    $host = new FakeHost(home: '/home/dev', executables: ['pie' => PIE]);

    runExtensions($host, runner(), [Key::ENTER, Key::ENTER]);

    expect($host->searches)->toContain(['pie', ['/home/dev/.local/bin']]);
});

it('stops when nothing is selected', function () {
    $runner = runner();

    // Ctrl+A clears the list, since every installable row starts selected.
    $shared = runExtensions(new FakeHost(executables: ['pie' => PIE]), $runner, [Key::ENTER, Key::CTRL_A, Key::ENTER]);

    expect($shared['extensions_note'])->toBe('None selected.')
        ->and($shared)->not->toHaveKey('extension_results')
        ->and(array_filter($runner->commands, fn (array $command): bool => in_array('install', $command, true)))->toBe([]);
});

it('stops when PIE is missing and installing it is declined', function () {
    $host = new FakeHost;
    $runner = runner();

    $shared = runExtensions($host, $runner, [Key::ENTER, 'n', Key::ENTER]);

    expect($shared['extensions_note'])->toBe('PIE is not installed, and you declined to install it.')
        ->and($host->downloads)->toBe([])
        ->and($runner->commands)->toBe([[PHP_CONFIG, '--php-binary']]);
});

it('downloads PIE, has it verify itself, and goes on to install with it', function () {
    $host = new FakeHost(os: 'Linux', home: '/home/dev', loaded: ['pcurl']);
    $runner = runner();

    // Accept the offer, accept installing PIE, accept the list.
    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER, Key::ENTER]);

    expect($host->downloads)->toBe([['https://github.com/php/pie/releases/latest/download/pie.phar', '/home/dev/.local/bin/pie']])
        ->and($host->removed)->toBe([])
        ->and($shared['pie_binary'])->toBe('/home/dev/.local/bin/pie')
        ->and($runner->commands)->toContain([PHP, '/home/dev/.local/bin/pie', 'self-verify'])
        ->and($runner->commands)->toContain([PHP, '/home/dev/.local/bin/pie', 'install', 'php-io-extensions/epoll:^0.10', '--with-php-config='.PHP_CONFIG])
        ->and($shared['extension_results'])->toBe(['epoll' => 'installed']);
});

it('offers to install PIE when the one on PATH does not run under this PHP binary', function () {
    $host = new FakeHost(home: '/home/dev', executables: ['pie' => PIE]);
    $runner = runner([[PIE.' --version', 1, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER, Key::ENTER]);

    expect($shared['pie_binary'])->toBe('/home/dev/.local/bin/pie')
        ->and($host->downloads)->toHaveCount(1);
});

it('does not take a pie on PATH that is some other program', function () {
    $host = new FakeHost(executables: ['pie' => PIE]);
    $runner = runner([[PIE.' --version', 0, 'Python Installs Everything 3.1']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, 'n', Key::ENTER]);

    expect($shared['extensions_note'])->toBe('PIE is not installed, and you declined to install it.');
});

it('stops when PIE cannot be downloaded', function () {
    $host = new FakeHost(downloads_arrive: false);
    $runner = runner();

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extensions_note'])->toBe('PIE could not be downloaded from https://github.com/php/pie/releases/latest/download/pie.phar.')
        ->and($shared)->not->toHaveKey('pie_binary')
        ->and($runner->commands)->toBe([[PHP_CONFIG, '--php-binary']]);
});

it('removes a downloaded PIE that fails its own verification, and stops', function () {
    $host = new FakeHost(home: '/home/dev');
    $runner = runner([['self-verify', 1, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extensions_note'])->toBe('The downloaded PIE failed its own verification and was removed.')
        ->and($host->removed)->toBe(['/home/dev/.local/bin/pie'])
        ->and($shared)->not->toHaveKey('pie_binary');
});

it('stops when PIE is missing and there is no home directory to install it into', function () {
    $host = new FakeHost(home: null);

    $shared = runExtensions($host, runner(), [Key::ENTER]);

    expect($shared['extensions_note'])->toBe('PIE is not installed, and there is no home directory to install it into.')
        ->and($host->searches)->toContain(['pie', []]);
});

it('finds the php-config that sits beside a versioned PHP binary', function () {
    $host = new FakeHost(php: '/usr/bin/php8.4', os: 'Linux', loaded: ['pcurl'], executables: ['pie' => PIE]);
    $runner = runner([['/usr/bin/php-config8.4 --php-binary', 0, '/usr/bin/php8.4']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBe('/usr/bin/php-config8.4')
        ->and($runner->commands)->toContain(['/usr/bin/php8.4', PIE, 'install', 'php-io-extensions/epoll:^0.10', '--with-php-config=/usr/bin/php-config8.4']);
});

it('takes a php-config from PATH when it reports the running PHP binary', function () {
    $host = new FakeHost(executables: ['pie' => PIE, 'php-config' => '/usr/local/bin/php-config']);
    $runner = runner([[PHP_CONFIG.' --php-binary', 127, ''], ['/usr/local/bin/php-config --php-binary', 0, PHP]]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBe('/usr/local/bin/php-config');
});

it('stops when the only php-config builds for another PHP', function () {
    $host = new FakeHost(executables: ['pie' => PIE, 'php-config' => '/usr/local/bin/php-config']);
    $runner = runner([[PHP_CONFIG.' --php-binary', 127, ''], ['/usr/local/bin/php-config --php-binary', 0, '/usr/local/bin/php']]);

    $shared = runExtensions($host, $runner, [Key::ENTER]);

    expect($shared['extensions_note'])->toBe('The php-config on this machine builds for /usr/local/bin/php, not for '.PHP.'. Install the development files for '.PHP.' first.')
        ->and($shared)->not->toHaveKey('extension_results')
        ->and($host->searches)->toBe([['php-config', []]]);
});

it('leaves the php-config to PIE when the machine has none', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['pcurl'], executables: ['pie' => PIE]);
    $runner = runner([[PHP_CONFIG.' --php-binary', 127, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBeNull()
        ->and($runner->commands)->toContain([PHP, PIE, 'install', 'php-io-extensions/epoll:^0.10'])
        ->and($shared['extension_results'])->toBe(['epoll' => 'installed']);
});

it('reports a failed build and still installs the next extension', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => PIE]);
    $runner = runner([['install php-io-extensions/kqueue', 2, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extension_results'])->toBe(['kqueue' => 'failed (PIE exit code 2)', 'pcurl' => 'installed'])
        ->and($runner->commands)->not->toContain([PHP, '--ri', 'kqueue']);
});

it('reports an extension PIE installed that the PHP binary still does not load', function () {
    $host = new FakeHost(os: 'Darwin', loaded: ['kqueue'], executables: ['pie' => PIE]);
    $runner = runner([['--ri pcurl', 1, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extension_results'])->toBe(['pcurl' => 'installed by PIE, but '.PHP.' does not load it']);
});

it('passes what PIE prints to the output callback when there is no terminal to hand over', function () {
    $host = new FakeHost(os: 'Darwin', loaded: ['kqueue'], executables: ['pie' => PIE]);
    $runner = runner([['install', 0, 'Install complete']]);
    $printed = [];

    Prompt::fake([Key::ENTER, Key::ENTER]);
    $shared = ['interactive' => true, 'output_callback' => function (string $type, string $buffer) use (&$printed): void {
        $printed[] = $buffer;
    }];
    (new ExtensionsFlow($host, $runner))->run($shared);

    expect($printed)->toBe(['Install complete']);
});

it('ends with a note, not an exception, when something inside the step throws', function () {
    // The shape of a dependency the global Composer install resolved differently: a method that is not there.
    $host = new class extends FakeHost
    {
        public function loaded(string $extension): bool
        {
            throw new Error('Call to undefined method Example::gone()');
        }
    };

    $shared = runExtensions($host, runner());

    expect($shared['extensions_note'])->toBe('Stopped by an error: Call to undefined method Example::gone()')
        ->and($shared)->not->toHaveKey('extension_results');
});

it('keeps the outcomes of extensions already installed when a later one throws', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => PIE]);
    $runner = new FakeProcessRunner(function (array $command): array {
        if (in_array('php-io-extensions/pcurl:^0.10', $command, true)) {
            throw new RuntimeException('The process could not be started.');
        }

        return match (true) {
            in_array('--version', $command, true) => [0, VERSION_LINE],
            in_array('--php-binary', $command, true) => [0, PHP],
            default => [0, ''],
        };
    });

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extension_results'])->toBe(['kqueue' => 'installed', 'pcurl' => 'failed (The process could not be started.)']);
});

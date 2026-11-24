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

    $shared = runExtensions(new FakeHost(os: 'Darwin', loaded: ['kqueue', 'multicurl']), $runner);

    expect($shared['extensions_note'])->toBe('Nothing to install: epoll (Linux only), kqueue (installed), multicurl (installed).')
        ->and($shared['extension_states'])->toBe([
            'php-io-extensions/epoll:^0.11' => 'Linux only',
            'php-io-extensions/kqueue:^0.11' => 'installed',
            'php-io-extensions/multicurl:^0.11' => 'installed',
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
        [PHP, PIE, 'install', 'php-io-extensions/kqueue:^0.11', '--with-php-config='.PHP_CONFIG],
        [PHP, '--ri', 'kqueue'],
        [PHP, PIE, 'install', 'php-io-extensions/multicurl:^0.11', '--with-php-config='.PHP_CONFIG],
        [PHP, '--ri', 'multicurl'],
    ])
        ->and($runner->on_terminal)->toBe([
            [PHP, PIE, 'install', 'php-io-extensions/kqueue:^0.11', '--with-php-config='.PHP_CONFIG],
            [PHP, PIE, 'install', 'php-io-extensions/multicurl:^0.11', '--with-php-config='.PHP_CONFIG],
        ])
        ->and($shared['pie_binary'])->toBe(PIE)
        ->and($shared['php_config'])->toBe(PHP_CONFIG)
        ->and($shared['extensions_selected'])->toBe(['php-io-extensions/kqueue:^0.11', 'php-io-extensions/multicurl:^0.11'])
        ->and($shared['extension_results'])->toBe(['kqueue' => 'installed', 'multicurl' => 'installed'])
        ->and($shared)->not->toHaveKey('extensions_note');
});

it('shows the other operating system\'s extension and an installed one as disabled rows', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['multicurl'], executables: ['pie' => PIE]);

    $shared = runExtensions($host, runner(), [Key::ENTER, Key::ENTER]);

    expect(Prompt::strippedContent())->toContain('◼ epoll — event loop waiting on Linux')
        ->and(Prompt::strippedContent())->toContain('– kqueue — event loop waiting on macOS (macOS only)')
        ->and(Prompt::strippedContent())->toContain('– multicurl — HTTP requests that run on the event loop (installed)')
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
    $host = new FakeHost(os: 'Linux', home: '/home/dev', loaded: ['multicurl']);
    $runner = runner();

    // Accept the offer, accept installing PIE, accept the list.
    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER, Key::ENTER]);

    expect($host->downloads)->toBe([['https://github.com/php/pie/releases/latest/download/pie.phar', '/home/dev/.local/bin/pie']])
        ->and($host->removed)->toBe([])
        ->and($shared['pie_binary'])->toBe('/home/dev/.local/bin/pie')
        ->and($runner->commands)->toContain([PHP, '/home/dev/.local/bin/pie', 'self-verify'])
        ->and($runner->commands)->toContain([PHP, '/home/dev/.local/bin/pie', 'install', 'php-io-extensions/epoll:^0.11', '--with-php-config='.PHP_CONFIG])
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
    $host = new FakeHost(php: '/usr/bin/php8.4', os: 'Linux', loaded: ['multicurl'], executables: ['pie' => PIE]);
    $runner = runner([['/usr/bin/php-config8.4 --php-binary', 0, '/usr/bin/php8.4']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBe('/usr/bin/php-config8.4')
        ->and($runner->commands)->toContain(['/usr/bin/php8.4', PIE, 'install', 'php-io-extensions/epoll:^0.11', '--with-php-config=/usr/bin/php-config8.4']);
});

it('takes a php-config from PATH when it reports the running PHP binary', function () {
    $host = new FakeHost(executables: ['pie' => PIE, 'php-config' => '/usr/local/bin/php-config']);
    $runner = runner([[PHP_CONFIG.' --php-binary', 127, ''], ['/usr/local/bin/php-config --php-binary', 0, PHP]]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBe('/usr/local/bin/php-config');
});

it('leaves the php-config to PIE when the only one builds for another PHP', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['multicurl'], executables: ['pie' => PIE, 'php-config' => '/usr/local/bin/php-config']);
    $runner = runner([[PHP_CONFIG.' --php-binary', 127, ''], ['/usr/local/bin/php-config --php-binary', 0, '/usr/local/bin/php']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBeNull()
        ->and($shared)->not->toHaveKey('extensions_note')
        ->and($runner->commands)->toContain([PHP, PIE, 'install', 'php-io-extensions/epoll:^0.11'])
        ->and($shared['extension_results'])->toBe(['epoll' => 'installed']);
});

it('takes the php-config of a PHP built without the CLI in its SAPI list by its prefix, version and thread safety', function () {
    // Homebrew's php@8.4-zts: its php-config answers --php-binary with NONE.
    $zts = '/opt/homebrew/Cellar/php@8.4-zts/8.4.27';
    $host = new FakeHost(php: "{$zts}/bin/php", os: 'Darwin', loaded: ['kqueue'], executables: ['pie' => PIE, 'php-config' => '/opt/homebrew/bin/php-config'], bindir: "{$zts}/bin", prefix: $zts, version_id: 80427, zts: true);
    $runner = runner([
        ["{$zts}/bin/php-config --php-binary", 0, 'NONE'],
        ["{$zts}/bin/php-config --prefix", 0, $zts],
        ["{$zts}/bin/php-config --vernum", 0, '80427'],
        ["{$zts}/bin/php-config --extension-dir", 0, "{$zts}/pecl/20240924-zts"],
        ['/opt/homebrew/bin/php-config --php-binary', 0, '/opt/homebrew/Cellar/php/8.5.12_1/bin/php'],
    ]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBe("{$zts}/bin/php-config")
        ->and($runner->commands)->not->toContain(['/opt/homebrew/bin/php-config', '--php-binary'])
        ->and($runner->commands)->toContain(["{$zts}/bin/php", PIE, 'install', 'php-io-extensions/multicurl:^0.11', "--with-php-config={$zts}/bin/php-config"]);
});

it('does not take a php-config that names no binary when it is for another version or thread safety', function (string $vernum, string $extension_dir) {
    $host = new FakeHost(os: 'Linux', loaded: ['multicurl'], executables: ['pie' => PIE], prefix: '/opt/php', version_id: 80427, zts: false);
    $runner = runner([
        [PHP_CONFIG.' --php-binary', 0, 'NONE'],
        [PHP_CONFIG.' --prefix', 0, '/opt/php'],
        [PHP_CONFIG.' --vernum', 0, $vernum],
        [PHP_CONFIG.' --extension-dir', 0, $extension_dir],
    ]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBeNull();
})->with([
    'another version' => ['80512', '/opt/php/lib/php/extensions/no-debug-non-zts-20250925'],
    'thread safe' => ['80427', '/opt/php/lib/php/extensions/no-debug-zts-20240924'],
]);

it('leaves the php-config to PIE when the machine has none', function () {
    $host = new FakeHost(os: 'Linux', loaded: ['multicurl'], executables: ['pie' => PIE]);
    $runner = runner([[PHP_CONFIG.' --php-binary', 127, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['php_config'])->toBeNull()
        ->and($runner->commands)->toContain([PHP, PIE, 'install', 'php-io-extensions/epoll:^0.11'])
        ->and($shared['extension_results'])->toBe(['epoll' => 'installed']);
});

it('reports a failed build and still installs the next extension', function () {
    $host = new FakeHost(os: 'Darwin', executables: ['pie' => PIE]);
    $runner = runner([['install php-io-extensions/kqueue', 2, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extension_results'])->toBe(['kqueue' => 'failed (PIE exit code 2)', 'multicurl' => 'installed'])
        ->and($runner->commands)->not->toContain([PHP, '--ri', 'kqueue']);
});

it('reports an extension PIE installed that the PHP binary still does not load', function () {
    $host = new FakeHost(os: 'Darwin', loaded: ['kqueue'], executables: ['pie' => PIE]);
    $runner = runner([['--ri multicurl', 1, '']]);

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extension_results'])->toBe(['multicurl' => 'installed by PIE, but '.PHP.' does not load it']);
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
        if (in_array('php-io-extensions/multicurl:^0.11', $command, true)) {
            throw new RuntimeException('The process could not be started.');
        }

        return match (true) {
            in_array('--version', $command, true) => [0, VERSION_LINE],
            in_array('--php-binary', $command, true) => [0, PHP],
            default => [0, ''],
        };
    });

    $shared = runExtensions($host, $runner, [Key::ENTER, Key::ENTER]);

    expect($shared['extension_results'])->toBe(['kqueue' => 'installed', 'multicurl' => 'failed (The process could not be started.)']);
});

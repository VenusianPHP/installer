<?php

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Venusian\Installer\Enums\InstallerPackage;
use Venusian\Installer\ProcessRunner;
use Venusian\Installer\ReleaseChannel\PackagistReleaseWatcher;

function packagistP2Body(string $version): string
{
    return json_encode([
        'packages' => [
            InstallerPackage::COMPOSER->value => [
                ['version' => $version],
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

function makeReleaseWatcher(array $overrides = []): PackagistReleaseWatcher
{
    $processRunner = $overrides['processRunner'] ?? Mockery::mock(ProcessRunner::class);
    unset($overrides['processRunner']);

    $defaults = [
        'httpClient' => static function (): never {
            throw new RuntimeException('httpClient must not be called');
        },
        'clock' => static fn (): int => 1_700_000_000,
        'cacheDirectory' => sys_get_temp_dir(),
        'confirm' => static function (): never {
            throw new RuntimeException('confirm must not be called');
        },
        'installedVersionResolver' => static fn (): string => '0.8.0',
        'reExec' => static function (): never {
            throw new RuntimeException('reExec must not be called');
        },
        'terminate' => static function (): never {
            throw new RuntimeException('terminate must not be called');
        },
        'composerFinder' => static fn (): ?string => '/usr/bin/composer',
        'binaryFinder' => static fn (): ?string => '/usr/local/bin/venusian',
    ];

    $args = array_merge($defaults, $overrides);

    return new PackagistReleaseWatcher(
        processRunner: $processRunner,
        httpClient: $args['httpClient'],
        clock: $args['clock'],
        cacheDirectory: $args['cacheDirectory'],
        confirm: $args['confirm'],
        installedVersionResolver: $args['installedVersionResolver'],
        reExec: $args['reExec'],
        terminate: $args['terminate'],
        composerFinder: $args['composerFinder'],
        binaryFinder: $args['binaryFinder'],
    );
}

function interactiveInput(): ArrayInput
{
    $input = new ArrayInput([]);
    $input->setInteractive(true);

    return $input;
}

beforeEach(function () {
    $this->cacheDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'venusian-installer-test-'.uniqid('cache', true);
    mkdir($this->cacheDir, 0777, true);

    $this->previousNoUpdate = getenv(InstallerPackage::NO_UPDATE_CHECK_ENV->value);
    putenv(InstallerPackage::NO_UPDATE_CHECK_ENV->value);
});

afterEach(function () {
    $env = InstallerPackage::NO_UPDATE_CHECK_ENV->value;
    if ($this->previousNoUpdate === false) {
        putenv($env);
    } else {
        putenv($env.'='.$this->previousNoUpdate);
    }

    if (is_dir($this->cacheDir)) {
        foreach (glob($this->cacheDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->cacheDir);
    }
});

it('offers an update when latest is greater than installed, then re-execs and terminates', function () {
    $reExecCommand = null;
    $terminateCode = null;

    $runner = Mockery::mock(ProcessRunner::class);
    $runner->shouldReceive('run')
        ->once()
        ->withArgs(function (array $command, ?string $cwd = null, mixed $outputCallback = null, bool $inheritTty = false): bool {
            expect($command)->toBe([
                '/usr/bin/composer',
                'global',
                'require',
                'venusian/installer:^0.8.3',
                '--with-all-dependencies',
                '--no-interaction',
            ])
                ->and($inheritTty)->toBeTrue();

            return true;
        })
        ->andReturn(0);

    $confirmLabel = null;
    $watcher = makeReleaseWatcher([
        'processRunner' => $runner,
        'cacheDirectory' => $this->cacheDir,
        'httpClient' => function (string $url, array $headers): array {
            expect($url)->toBe(InstallerPackage::PACKAGIST_P2_URL->value);

            return [
                'status' => 200,
                'headers' => "HTTP/1.1 200 OK\r\n\r\n",
                'body' => packagistP2Body('0.8.3'),
            ];
        },
        'confirm' => function (string $label) use (&$confirmLabel): bool {
            $confirmLabel = $label;

            return true;
        },
        'installedVersionResolver' => static fn (): string => '0.8.0',
        'reExec' => function (array $command) use (&$reExecCommand): int {
            $reExecCommand = $command;

            return 17;
        },
        'terminate' => function (int $code) use (&$terminateCode): void {
            $terminateCode = $code;
        },
    ]);

    $watcher->maybeOfferUpdate(
        interactiveInput(),
        new BufferedOutput,
        ['venusian', 'new', 'demo'],
        '0.8.0',
    );

    expect($confirmLabel)->toBe('Would you like to update now?')
        ->and($reExecCommand)->toBe(['/usr/local/bin/venusian', 'new', 'demo'])
        ->and($terminateCode)->toBe(17);
});

it('does not offer an update when installed is greater than or equal to latest', function () {
    $watcher = makeReleaseWatcher([
        'cacheDirectory' => $this->cacheDir,
        'httpClient' => static fn (): array => [
            'status' => 200,
            'headers' => "HTTP/1.1 200 OK\r\n\r\n",
            'body' => packagistP2Body('0.8.2'),
        ],
        'installedVersionResolver' => static fn (): string => '0.8.2',
    ]);

    $watcher->maybeOfferUpdate(
        interactiveInput(),
        new BufferedOutput,
        ['venusian', 'new', 'demo'],
    );
})->throwsNoExceptions();

it('skips the update check when the input is non-interactive', function () {
    $input = new ArrayInput([]);
    $input->setInteractive(false);

    makeReleaseWatcher([
        'cacheDirectory' => $this->cacheDir,
    ])->maybeOfferUpdate($input, new BufferedOutput, ['venusian', 'new', 'demo']);
})->throwsNoExceptions();

it('skips the update check when VENUSIAN_INSTALLER_NO_UPDATE_CHECK is 1', function () {
    putenv(InstallerPackage::NO_UPDATE_CHECK_ENV->value.'=1');

    makeReleaseWatcher([
        'cacheDirectory' => $this->cacheDir,
    ])->maybeOfferUpdate(
        interactiveInput(),
        new BufferedOutput,
        ['venusian', 'new', 'demo'],
    );
})->throwsNoExceptions();

it('skips the update check when the first non-option argv token is completion', function () {
    makeReleaseWatcher([
        'cacheDirectory' => $this->cacheDir,
    ])->maybeOfferUpdate(
        interactiveInput(),
        new BufferedOutput,
        ['venusian', '--ansi', 'completion'],
    );
})->throwsNoExceptions();

it('uses the cached Packagist body when the cache is within TTL and does not call httpClient', function () {
    $bodyPath = $this->cacheDir.DIRECTORY_SEPARATOR.InstallerPackage::CACHE_BODY_FILENAME->value;
    file_put_contents($bodyPath, packagistP2Body('0.9.0'));
    $mtime = filemtime($bodyPath);

    $confirmLabel = null;
    $watcher = makeReleaseWatcher([
        'cacheDirectory' => $this->cacheDir,
        'clock' => static fn () => $mtime,
        'confirm' => function (string $label) use (&$confirmLabel): bool {
            $confirmLabel = $label;

            return false;
        },
        'installedVersionResolver' => static fn (): string => '0.8.0',
    ]);

    $watcher->maybeOfferUpdate(
        interactiveInput(),
        new BufferedOutput,
        ['venusian', 'new', 'demo'],
    );

    expect($confirmLabel)->toBe('Would you like to update now?');
});

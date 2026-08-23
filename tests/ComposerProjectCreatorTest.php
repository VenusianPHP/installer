<?php

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Venusian\Installer\ComposerProjectCreator;
use Venusian\Installer\Exceptions\ComposerNotFoundException;

it('builds create-project argv with the venusian skeleton pin and dist flags', function () {
    $creator = new ComposerProjectCreator(
        executableFinder: Mockery::mock(ExecutableFinder::class),
        processFactory: static fn (): Process => Mockery::mock(Process::class),
    );

    expect($creator->buildCreateProjectCommand('/usr/bin/composer', '/tmp/demo'))->toBe([
        '/usr/bin/composer',
        'create-project',
        'venusian/venusian:^0.8.2',
        '/tmp/demo',
        '--remove-vcs',
        '--prefer-dist',
    ]);
});

it('throws ComposerNotFoundException when the finder returns null', function () {
    $finder = Mockery::mock(ExecutableFinder::class);
    $finder->shouldReceive('find')->once()->with('composer')->andReturn(null);

    $creator = new ComposerProjectCreator(
        executableFinder: $finder,
        processFactory: static function (): Process {
            throw new RuntimeException('processFactory must not run when composer is missing');
        },
    );

    $creator->create('/tmp/demo');
})->throws(ComposerNotFoundException::class);

it('returns the process exit code', function () {
    $finder = Mockery::mock(ExecutableFinder::class);
    $finder->shouldReceive('find')->once()->with('composer')->andReturn('/usr/bin/composer');

    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(7);

    $creator = new ComposerProjectCreator(
        executableFinder: $finder,
        processFactory: static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($creator->create('/tmp/demo'))->toBe(7);
});

it('returns 1 when the process exit code is null', function () {
    $finder = Mockery::mock(ExecutableFinder::class);
    $finder->shouldReceive('find')->once()->with('composer')->andReturn('/usr/bin/composer');

    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(null);

    $creator = new ComposerProjectCreator(
        executableFinder: $finder,
        processFactory: static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($creator->create('/tmp/demo'))->toBe(1);
});

it('invokes the output callback with process buffer', function () {
    $finder = Mockery::mock(ExecutableFinder::class);
    $finder->shouldReceive('find')->once()->with('composer')->andReturn('/usr/bin/composer');

    $seen = [];
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')
        ->once()
        ->andReturnUsing(function (?callable $callback = null) use (&$seen): int {
            $callback(Process::OUT, 'creating...');

            return 0;
        });
    $process->shouldReceive('getExitCode')->once()->andReturn(0);

    $capturedCommand = null;
    $creator = new ComposerProjectCreator(
        executableFinder: $finder,
        processFactory: static function (array $command, ?string $cwd = null) use ($process, &$capturedCommand): Process {
            $capturedCommand = $command;
            expect($cwd)->toBeNull();

            return $process;
        },
    );

    $exit = $creator->create('/tmp/demo', function (string $type, string $buffer) use (&$seen): void {
        $seen[] = [$type, $buffer];
    });

    expect($exit)->toBe(0)
        ->and($seen)->toBe([[Process::OUT, 'creating...']])
        ->and($capturedCommand)->toBe([
            '/usr/bin/composer',
            'create-project',
            'venusian/venusian:^0.8.2',
            '/tmp/demo',
            '--remove-vcs',
            '--prefer-dist',
        ]);
});

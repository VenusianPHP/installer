<?php

use Symfony\Component\Process\Process;
use Venusian\Installer\ProcessRunner;

it('returns the process exit code from run', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(3);

    $runner = new ProcessRunner(
        static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($runner->run(['false']))->toBe(3);
});

it('returns 1 from run when the process exit code is null', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(null);

    $runner = new ProcessRunner(
        static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($runner->run(['hanging']))->toBe(1);
});

it('reports succeeds as true when run returns 0', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(0);

    $runner = new ProcessRunner(
        static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($runner->succeeds(['true']))->toBeTrue();
});

it('reports succeeds as false when run returns non-zero', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(1);

    $runner = new ProcessRunner(
        static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($runner->succeeds(['false']))->toBeFalse();
});

it('returns trimmed stdout from output when the exit code is 0', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(0);
    $process->shouldReceive('getOutput')->once()->andReturn("  hello world  \n");

    $runner = new ProcessRunner(
        static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($runner->output(['echo', 'hello world']))->toBe('hello world');
});

it('returns null from output when the exit code is non-zero', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(1);
    $process->shouldNotReceive('getOutput');

    $runner = new ProcessRunner(
        static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($runner->output(['false']))->toBeNull();
});

it('returns null from output when the exit code is null', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('run')->once();
    $process->shouldReceive('getExitCode')->once()->andReturn(null);
    $process->shouldNotReceive('getOutput');

    $runner = new ProcessRunner(
        static fn (array $command, ?string $cwd = null): Process => $process,
    );

    expect($runner->output(['hanging']))->toBeNull();
});

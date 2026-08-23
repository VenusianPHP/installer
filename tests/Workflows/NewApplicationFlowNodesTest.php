<?php

use Symfony\Component\Process\Process;
use Venusian\Installer\Workflows\Misc\ComposerFinderNode;
use Venusian\Installer\Workflows\NewApplication\NewApplicationStartNode;
use Venusian\Installer\Workflows\NewApplication\ProjectCreationNode;

it('sets shared.directory in start post and returns find-composer', function () {
    $node = new NewApplicationStartNode('/tmp/demo');
    $shared = [];

    $action = $node->post($shared, null, null);

    expect($shared['directory'])->toBe('/tmp/demo')
        ->and($action)->toBe('find-composer');
});

it('sets composer_binary and returns default when the finder hits', function () {
    $node = new ComposerFinderNode;
    $shared = [];

    $action = $node->post($shared, null, '/usr/bin/composer');

    expect($shared['composer_binary'])->toBe('/usr/bin/composer')
        ->and($action)->toBe('default')
        ->and($shared)->not->toHaveKey('success')
        ->and($shared)->not->toHaveKey('errors');
});

it('sets success false and composer errors when the finder misses', function () {
    $node = new ComposerFinderNode;
    $shared = [];

    $action = $node->post($shared, null, null);

    expect($shared['success'])->toBeFalse()
        ->and($shared['errors'])->toBe([
            'composer' => 'Could not find the composer executable.',
        ])
        ->and($action)->toBeNull()
        ->and($shared)->not->toHaveKey('composer_binary');
});

it('builds the skeleton create-project command in project creation prep', function () {
    $callback = static function (string $type, string $buffer): void {};
    $shared = [
        'composer_binary' => '/usr/bin/composer',
        'directory' => '/tmp/demo',
        'output_callback' => $callback,
    ];

    $prep = (new ProjectCreationNode)->prep($shared);

    expect($prep['command'])->toBe([
        '/usr/bin/composer',
        'create-project',
        'venusian/venusian:^0.8.2',
        '/tmp/demo',
        '--remove-vcs',
        '--prefer-dist',
    ])
        ->and($prep['factory'])->toBeCallable()
        ->and($prep['output_callback'])->toBe($callback);
});

it('sets success true on project creation post when the exit code is 0', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('getExitCode')->once()->andReturn(0);
    $process->shouldNotReceive('getExitCodeText');

    $shared = [];
    $action = (new ProjectCreationNode)->post($shared, null, $process);

    expect($shared['success'])->toBeTrue()
        ->and($shared)->not->toHaveKey('errors')
        ->and($action)->toBeNull();
});

it('sets success false and errors on project creation post when the exit code is non-zero', function () {
    $process = Mockery::mock(Process::class);
    $process->shouldReceive('getExitCode')->andReturn(2);
    $process->shouldReceive('getExitCodeText')->once()->andReturn('Misuse of shell builtins');

    $shared = [];
    $action = (new ProjectCreationNode)->post($shared, null, $process);

    expect($shared['success'])->toBeFalse()
        ->and($shared['errors'])->toBe([
            'message' => 'Misuse of shell builtins',
            'code' => 2,
        ])
        ->and($action)->toBeNull();
});

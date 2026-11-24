<?php

use Venusian\Installer\Tests\Fakes\FakeHost;
use Venusian\Installer\Tests\Fakes\FakeProcessRunner;
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

it('runs the composer it finds under the PHP binary running the installer', function () {
    $node = new ComposerFinderNode(new FakeHost(executables: ['composer' => '/usr/bin/composer']));
    $shared = [];

    $action = $node->_run($shared);

    expect($shared['composer_command'])->toBe(['/opt/php/bin/php', '/usr/bin/composer'])
        ->and($action)->toBe('create-project')
        ->and($shared)->not->toHaveKey('success')
        ->and($shared)->not->toHaveKey('errors');
});

it('sets success false and composer errors when composer is not on PATH', function () {
    $node = new ComposerFinderNode(new FakeHost);
    $shared = [];

    $action = $node->_run($shared);

    expect($shared['success'])->toBeFalse()
        ->and($shared['errors'])->toBe([
            'composer' => 'Could not find the composer executable.',
        ])
        ->and($action)->toBeNull()
        ->and($shared)->not->toHaveKey('composer_command');
});

it('creates the skeleton project with the composer command and returns offer-extensions', function () {
    $runner = new FakeProcessRunner(fn (array $command): array => [0, 'creating']);
    $printed = [];
    $shared = [
        'composer_command' => ['/opt/php/bin/php', '/usr/bin/composer'],
        'directory' => '/tmp/demo',
        'output_callback' => function (string $type, string $buffer) use (&$printed): void {
            $printed[] = $buffer;
        },
    ];

    $action = (new ProjectCreationNode($runner))->_run($shared);

    expect($runner->commands)->toBe([[
        '/opt/php/bin/php',
        '/usr/bin/composer',
        'create-project',
        'venusian/venusian:^0.11.1',
        '/tmp/demo',
        '--remove-vcs',
        '--prefer-dist',
    ]])
        ->and($printed)->toBe(['creating'])
        ->and($shared['success'])->toBeTrue()
        ->and($shared)->not->toHaveKey('errors')
        ->and($action)->toBe('offer-extensions');
});

it('sets success false and errors when create-project exits non-zero', function () {
    $runner = new FakeProcessRunner(fn (array $command): array => [2, '']);
    $shared = ['composer_command' => ['/usr/bin/composer'], 'directory' => '/tmp/demo'];

    $action = (new ProjectCreationNode($runner))->_run($shared);

    expect($shared['success'])->toBeFalse()
        ->and($shared['errors'])->toBe([
            'message' => 'Misuse of shell builtins',
            'code' => 2,
        ])
        ->and($action)->toBeNull();
});

<?php

use Symfony\Component\Process\Process;
use Venusian\Installer\Enums\InstallerPackage;

it('reports the version composer.json declares from bin/venusian', function () {
    $root = dirname(__DIR__);
    $manifest = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $process = new Process(
        [PHP_BINARY, 'bin/venusian', '--version', '--no-interaction', '--no-ansi'],
        $root,
        [InstallerPackage::NO_UPDATE_CHECK_ENV->value => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and(trim($process->getOutput()))->toBe('Venusian PHP Application Installer '.$manifest['version']);
});

it('registers new, install:ext and install:toolkit-ext in bin/venusian', function () {
    $process = new Process(
        [PHP_BINARY, 'bin/venusian', 'list', '--raw', '--no-interaction', '--no-ansi'],
        dirname(__DIR__),
        [InstallerPackage::NO_UPDATE_CHECK_ENV->value => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/^new\s+Create a new Venusian PHP application$/m')
        ->and($process->getOutput())->toMatch('/^install:ext\s+Install Venusian\'s first-party PHP extensions through PIE$/m')
        ->and($process->getOutput())->toMatch('/^install:toolkit-ext\s+Install the PHP extensions Topside\'s toolkits run on through PIE$/m')
        ->and($process->getOutput())->not->toContain('install:sdk');
});

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

it('registers the new command in bin/venusian', function () {
    $process = new Process(
        [PHP_BINARY, 'bin/venusian', 'list', '--raw', '--no-interaction', '--no-ansi'],
        dirname(__DIR__),
        [InstallerPackage::NO_UPDATE_CHECK_ENV->value => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/^new\s+Create a new Venusian PHP application$/m');
});

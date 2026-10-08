<?php

use Symfony\Component\Console\Tester\CommandTester;
use Venusian\Installer\Console\Commands\InstallSdkCommand;
use Venusian\Installer\Tests\Fakes\FakeHost;
use Venusian\Installer\Tests\Fakes\FakeProcessRunner;

/*
 * install:sdk requires venusian/build globally; the installer then finds
 * it by package type and the build command appears.
 */
it('requires venusian/build globally through composer and names the new command', function () {
    $runner = new FakeProcessRunner(fn (array $command): array => [0, '']);
    $tester = new CommandTester(new InstallSdkCommand($runner, new FakeHost(executables: ['composer' => '/usr/local/bin/composer'])));

    expect($tester->execute([]))->toBe(0)
        ->and($runner->commands)->toBe([['/usr/local/bin/composer', 'global', 'require', 'venusian/build', '--no-interaction']])
        ->and($tester->getDisplay())->toContain('venusian build');
});

it('fails with composer\'s output when the require fails', function () {
    $runner = new FakeProcessRunner(fn (array $command): array => [1, 'Could not find package venusian/build']);
    $tester = new CommandTester(new InstallSdkCommand($runner, new FakeHost(executables: ['composer' => '/usr/local/bin/composer'])));

    expect($tester->execute([]))->toBe(1)
        ->and($tester->getDisplay())->toContain('Could not find package venusian/build');
});

it('says when composer is missing', function () {
    $runner = new FakeProcessRunner(fn (array $command): array => [0, '']);
    $tester = new CommandTester(new InstallSdkCommand($runner, new FakeHost(executables: [])));

    expect($tester->execute([]))->toBe(1)
        ->and($tester->getDisplay())->toContain('composer')
        ->and($runner->commands)->toBe([]);
});

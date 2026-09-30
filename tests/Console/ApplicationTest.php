<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Venusian\Installer\Console\Application;
use Venusian\Installer\ReleaseChannel\PackagistReleaseWatcher;

beforeEach(function () {
    $this->previousArgv = $_SERVER['argv'] ?? null;
});

afterEach(function () {
    $_SERVER['argv'] = $this->previousArgv;
});

it('offers an update with argv and its own version before running the command', function () {
    $_SERVER['argv'] = ['venusian', 'probe-command'];
    $order = [];

    $watcher = Mockery::mock(PackagistReleaseWatcher::class);
    $watcher->shouldReceive('maybeOfferUpdate')
        ->once()
        ->withArgs(function (InputInterface $input, OutputInterface $output, array $argv, ?string $version) use (&$order): bool {
            $order[] = 'watcher';

            return $argv === ['venusian', 'probe-command'] && $version === '9.9.9';
        });

    $app = new Application('Venusian PHP Application Installer', '9.9.9', $watcher);
    $app->setAutoExit(false);
    $app->addCommand(
        (new Command('probe-command'))->setCode(function () use (&$order): int {
            $order[] = 'command';

            return Command::SUCCESS;
        })
    );

    $exit = $app->run(new ArrayInput(['command' => 'probe-command']), new BufferedOutput);

    expect($exit)->toBe(0)
        ->and($order)->toBe(['watcher', 'command']);
});

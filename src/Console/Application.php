<?php

namespace Venusian\Installer\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Venusian\Installer\ReleaseChannel\PackagistReleaseWatcher;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    public function __construct(
        string $name = 'UNKNOWN',
        string $version = 'UNKNOWN',
        private ?PackagistReleaseWatcher $releaseWatcher = null,
    ) {
        parent::__construct($name, $version);
        $this->releaseWatcher ??= new PackagistReleaseWatcher;
    }

    /**
     * @throws Throwable
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string> $argv */
        $argv = $_SERVER['argv'] ?? [];

        $this->releaseWatcher->maybeOfferUpdate($input, $output, $argv, $this->getVersion());

        return parent::doRun($input, $output);
    }
}

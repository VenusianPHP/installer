<?php

namespace Venusian\Installer\Console\Commands;

use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Venusian\Installer\Enums\InstallerPackage;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[AsCommand(
    name: 'install:sdk',
    description: 'Install venusian/build, which adds the build command',
)]
class InstallSdkCommand extends Command
{
    public function __construct(
        private ?ProcessRunner $runner = null,
        private ?Host $host = null,
    ) {
        parent::__construct();
        $this->runner ??= new ProcessRunner;
        $this->host ??= new Host;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Prompt::setOutput($output);
        $composer = $this->host->find('composer');

        if (is_null($composer)) {
            error('composer is not on PATH; install Composer, then run install:sdk again.');

            return Command::FAILURE;
        }

        $captured = '';
        $exit = $this->runner->run(
            [$composer, 'global', 'require', InstallerPackage::BUILD->value, '--no-interaction'],
            null,
            function (string $type, string $buffer) use ($output, &$captured): void {
                $captured .= $buffer;
                $output->write($buffer);
            },
        );

        if ($exit !== 0) {
            error(trim("composer global require ".InstallerPackage::BUILD->value." failed.\n".$captured));

            return Command::FAILURE;
        }

        info('Installed. Run `venusian build` inside a Venusian app to compile it.');

        return Command::SUCCESS;
    }
}

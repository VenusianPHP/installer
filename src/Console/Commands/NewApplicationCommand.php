<?php

namespace Venusian\Installer\Console\Commands;

use Laravel\Prompts\Elements\Element;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Venusian\Installer\Actions\PrepareSharedBag;
use Venusian\Installer\Actions\ResolveDirectory;
use Venusian\Installer\Actions\SummarizeSteps;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;
use Venusian\Installer\Workflows\NewApplication\NewApplicationFlow;
use function Laravel\Prompts\callout;

#[AsCommand(
    name: 'new',
    description: 'Create a new Venusian PHP application',
)]
class NewApplicationCommand extends Command
{
    public function __construct(
        private ?Host $host = null,
        private ?ProcessRunner $runner = null,
    ) {
        parent::__construct();

        $this->host ??= new Host;
        $this->runner ??= new ProcessRunner;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name (or path) of the application')
            ;
    }

    /**
     * Exit 0 once the application exists, whatever became of the optional
     * steps after it; 1 otherwise.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = ResolveDirectory::run(
            rtrim((string) $input->getArgument('name'), '/\\')
        );

        $output->writeln("Path for new Venusian PHP application: <info>{$directory}</info>");

        $shared = PrepareSharedBag::run($input, $output);

        (new NewApplicationFlow($directory, $this->host, $this->runner))->run($shared);

        if(!array_key_exists('success', $shared))
        {
            callout('Installation Returned Malformed Response', [
                'The installer finished with a strange output. ',
                Element::keyValueList([
                    'shared' => json_encode($shared)
                ]),
            ], type: 'warning');

            return Command::FAILURE;
        }

        switch($shared['success']) {
            case true:
                callout(
                    label: 'Installation Successful!',
                    content: [
                        "<info>Application ready at {$directory}</info>",
                        ...SummarizeSteps::run($shared),
                        "",
                        "",
                        "",
                        "Build something amazing!"
                    ]
                );

                return Command::SUCCESS;

            case false:
                callout(
                    label: 'Installation Failed',
                    content: [
                        "Installer reported the following errors:",
                        Element::keyValueList($shared['errors'] ?? [
                            "Unknown" => "No Message Provided",
                        ]),
                    ],
                    type: 'error',
                );

                return Command::FAILURE;

            default:
                callout(
                    label: 'Installation Incomplete',
                    content: 'The installation process was interrupted and could not finish.',
                    type: 'warning',
                );

                return Command::FAILURE;
        }
    }
}

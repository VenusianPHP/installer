<?php

namespace Venusian\Installer\Console\Commands;

use Laravel\Prompts\Elements\Element;
use ProjectSaturnStudios\PocketFlow\Flow;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Venusian\Installer\Actions\PrepareSharedBag;
use Venusian\Installer\Actions\ResolveDirectory;
use Venusian\Installer\ComposerProjectCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Venusian\Installer\Workflows\Misc\ComposerFinderNode;
use Venusian\Installer\Workflows\NewApplication\NewApplicationStartNode;
use Venusian\Installer\Workflows\NewApplication\ProjectCreationNode;
use function Laravel\Prompts\callout;

#[AsCommand(
    name: 'new',
    description: 'Create a new Venusian PHP application',
)]
class NewApplicationCommand extends Command
{
    public function __construct(
        private ?ComposerProjectCreator $projectCreator = null,
        private ?Filesystem $filesystem = null,
    ) {
        parent::__construct();

        $this->projectCreator ??= new ComposerProjectCreator;
        $this->filesystem ??= new Filesystem;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name (or path) of the application')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = ResolveDirectory::run(
            trim((string) $input->getArgument('name'), '/\\')
        );

        $output->writeln("Path for new Venusian PHP application: <info>{$directory}</info>");

        $shared = PrepareSharedBag::run($input, $output);

        $start_node = new NewApplicationStartNode($directory);
        $finder_node = new ComposerFinderNode();
        $install_node = new ProjectCreationNode();
        $start_node->next($finder_node, 'find-composer');
        $finder_node->next($install_node);

        new Flow($start_node)->run($shared);

        if(array_key_exists('success', $shared))
        {
            switch($shared['success']) {
                case true:
                    callout(
                        label: 'Installation Successful!',
                        content: [
                            "<info>Application ready at {$directory}</info>",
                            "",
                            "",
                            "",
                            "Build something amazing!"
                        ]
                    );
                    break;

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
                    break;

                default:
                    callout(
                        label: 'Installation Incomplete',
                        content: 'The installation process was interrupted and could not finish.',
                        type: 'warning',
                    );
            }
        }
        else
        {
            callout('Installation Returned Malformed Response', [
                'The installer finished with a strange output. ',
                Element::keyValueList([
                    'shared' => json_encode($shared)
                ]),
            ], type: 'warn');
        }

        return 0;
    }
}
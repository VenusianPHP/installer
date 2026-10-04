<?php

namespace Venusian\Installer\Console\Commands;

use Laravel\Prompts\Elements\Element;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Workflows\Extensions\ExtensionsFlow;
use function Laravel\Prompts\callout;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[AsCommand(
    name: 'install:ext',
    description: 'Install Venusian\'s first-party PHP extensions through PIE',
)]
class InstallExtensionCommand extends Command
{
    public function __construct(
        private ?ExtensionsFlow $flow = null,
    ) {
        parent::__construct();

        $this->flow ??= new ExtensionsFlow(catalog: FirstPartyExtension::cases(), ask: false, preselect: false);
    }

    protected function configure(): void
    {
        $names = implode(', ', array_map(fn (FirstPartyExtension $extension): string => $extension->extension(), FirstPartyExtension::cases()));

        $this
            ->addArgument('ext', InputArgument::OPTIONAL, "The extension to install ({$names}). Omit it to choose from the list")
            ;
    }

    /**
     * Exit 0 when everything asked for is installed, or there was nothing to
     * install or nothing was selected; 1 otherwise.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $shared = [
            'interactive' => $input->isInteractive(),
            'extension_only' => $input->getArgument('ext'),
            'output_callback' => function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            },
        ];

        $this->flow->run($shared);

        if(array_key_exists('extension_results', $shared))
        {
            $installed = array_filter($shared['extension_results'], fn (string $outcome): bool => str_starts_with($outcome, 'installed') && !str_contains($outcome, 'but'));
            $complete = count($installed) === count($shared['extension_results']);

            callout(
                label: $complete ? 'Extensions Installed' : 'Extensions Not All Installed',
                content: [Element::keyValueList($shared['extension_results'])],
                type: $complete ? null : 'warning',
            );

            return $complete ? Command::SUCCESS : Command::FAILURE;
        }

        $note = $shared['extensions_note'];

        if(str_starts_with($note, 'Nothing to install') || $note === 'None selected.')
        {
            info($note);

            return Command::SUCCESS;
        }

        error($note);

        return Command::FAILURE;
    }
}

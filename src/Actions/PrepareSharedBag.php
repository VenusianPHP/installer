<?php

namespace Venusian\Installer\Actions;

use function Laravel\Prompts\info;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class PrepareSharedBag
{
    public static function run(InputInterface $input, OutputInterface $output): array
    {
        return [
            'success' => null,
            'name' => rtrim((string) $input->getArgument('name'), '/\\'),
            'actions' => [],
            'interactive' => $input->isInteractive(),
            'output_callback' => function (string $type, string $buffer) use ($output): void {
                $output->write($buffer);
            },
        ];
    }
}
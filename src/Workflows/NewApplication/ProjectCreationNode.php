<?php

namespace Venusian\Installer\Workflows\NewApplication;

use Symfony\Component\Process\Process;
use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\SkeletonPackage;
use function Laravel\Prompts\info;

class ProjectCreationNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        return [
            'factory' => static fn (array $command, ?string $cwd = null): Process => new Process($command, $cwd, null, null, null),
            'command' => [
                $shared['composer_binary'],
                'create-project',
                SkeletonPackage::VENUSIAN->value,
                $shared['directory'],
                '--remove-vcs',
                '--prefer-dist',
            ],
            'output_callback' => $shared['output_callback']
        ];
    }

    public function exec(mixed $prep_res): mixed
    {
        /** @var Process $process */
        $process = ($prep_res['factory'])($prep_res['command'], null);
        $outputCallback = $prep_res['output_callback'];

        $process->run(function (string $type, string $buffer) use ($outputCallback): void {
            if (! is_null($outputCallback)) {
                $outputCallback($type, $buffer);
            }
        });


        return $process;
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        /** @var Process $exec_res */
        $shared['success'] = $exec_res->getExitCode() === 0;
        if(!$shared['success']) {
            $shared['errors'] = [
                'message' => $exec_res->getExitCodeText(),
                'code' => $exec_res->getExitCode()
            ];
        }

        return null;
    }
}
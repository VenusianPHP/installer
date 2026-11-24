<?php

namespace Venusian\Installer\Workflows\NewApplication;

use ProjectSaturnStudios\PocketFlow\Node;
use Symfony\Component\Process\Process;
use Venusian\Installer\Enums\SkeletonPackage;
use Venusian\Installer\ProcessRunner;

class ProjectCreationNode extends Node
{
    public function __construct(
        private ProcessRunner $runner = new ProcessRunner,
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return [
            'command' => [
                ...$shared['composer_command'],
                'create-project',
                SkeletonPackage::VENUSIAN->value,
                $shared['directory'],
                '--remove-vcs',
                '--prefer-dist',
            ],
            'output_callback' => $shared['output_callback'] ?? null,
        ];
    }

    /**
     * @return int The exit code of `composer create-project`.
     */
    public function exec(mixed $prep_res): mixed
    {
        return $this->runner->run($prep_res['command'], null, $prep_res['output_callback']);
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['success'] = $exec_res === 0;

        if(!$shared['success']) {
            $shared['errors'] = [
                'message' => Process::$exitCodes[$exec_res] ?? 'Unknown error',
                'code' => $exec_res,
            ];

            return null;
        }

        return 'offer-extensions';
    }
}

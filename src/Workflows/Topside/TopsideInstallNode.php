<?php

namespace Venusian\Installer\Workflows\Topside;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\SkeletonPackage;
use Venusian\Installer\Enums\TopsidePackage;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;

/**
 * Requires venusian/topside in the application, then publishes its config
 * through the application's own console, both under the PHP binary running
 * the installer.
 */
class TopsideInstallNode extends Node
{
    public function __construct(
        private Host $host = new Host,
        private ProcessRunner $runner = new ProcessRunner,
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return [
            'composer' => $shared['composer_command'],
            'directory' => $shared['directory'],
            'output_callback' => $shared['output_callback'] ?? null,
        ];
    }

    /**
     * @return array{installed: bool, outcome: string}
     */
    public function exec(mixed $prep_res): mixed
    {
        $package = explode(':', TopsidePackage::COMPOSER->value)[0];

        $required = $this->runner->run(
            [...$prep_res['composer'], 'require', TopsidePackage::COMPOSER->value, '--no-interaction'],
            $prep_res['directory'],
            $prep_res['output_callback'],
        );

        if($required !== 0) {
            return ['installed' => false, 'outcome' => "composer require {$package} failed (exit code {$required})."];
        }

        $published = $this->runner->run(
            [
                $this->host->phpBinary(),
                SkeletonPackage::CONSOLE->value,
                'vendor:publish',
                '--tag='.TopsidePackage::CONFIG_TAG->value,
                '--no-interaction',
            ],
            $prep_res['directory'],
            $prep_res['output_callback'],
        );

        return [
            'installed' => true,
            'outcome' => $published === 0
                ? 'installed, config published'
                : "installed, but publishing its config failed (exit code {$published})",
        ];
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(!$exec_res['installed']) {
            $shared['topside_note'] = $exec_res['outcome'];

            return null;
        }

        $shared['topside_result'] = $exec_res['outcome'];

        return 'offer-toolkits';
    }
}

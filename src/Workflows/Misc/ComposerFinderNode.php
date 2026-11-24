<?php

namespace Venusian\Installer\Workflows\Misc;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Host;

/**
 * Finds composer on PATH and the command that runs it under the PHP binary
 * running the installer, so Composer resolves platform packages, and its
 * `@php` scripts run, for that PHP.
 */
class ComposerFinderNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return null;
    }

    /**
     * @return ?list<string> The command that runs composer, or null.
     */
    public function exec(mixed $prep_res): mixed
    {
        $composer = $this->host->find('composer');

        return is_null($composer) ? null : $this->host->phpCommand($composer);
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(is_null($exec_res)) {
            $shared['success'] = false;
            $shared['errors'] = [
                'composer' => 'Could not find the composer executable.',
            ];

            return null;
        }

        $shared['composer_command'] = $exec_res;

        return 'create-project';
    }
}

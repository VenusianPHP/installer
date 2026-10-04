<?php

namespace Venusian\Installer\Workflows\Extensions;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\PiePackage;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;

class PieFinderNode extends Node
{
    public function __construct(
        private Host $host = new Host,
        private ProcessRunner $runner = new ProcessRunner,
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return null;
    }

    /**
     * A PIE counts only when the PHP binary running the installer can run it.
     *
     * @return ?string Its path.
     */
    public function exec(mixed $prep_res): mixed
    {
        $home = $this->host->home();
        $pie = $this->host->find(
            PiePackage::BINARY->value,
            is_null($home) ? [] : [$home.'/'.PiePackage::INSTALL_DIRECTORY->value],
        );

        if(is_null($pie)) {
            return null;
        }

        $version = $this->runner->output([$this->host->phpBinary(), $pie, '--version']);

        return !is_null($version) && str_contains($version, PiePackage::VERSION_MARKER->value) ? $pie : null;
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(!is_null($exec_res)) {
            $shared['pie_binary'] = $exec_res;
            return 'select-extensions';
        }

        return 'install-pie';
    }
}

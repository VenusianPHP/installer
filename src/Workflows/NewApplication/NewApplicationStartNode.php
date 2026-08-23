<?php

namespace Venusian\Installer\Workflows\NewApplication;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Workflows\Misc\ComposerFinderNode;
use function Laravel\Prompts\info;

class NewApplicationStartNode extends Node
{
    public function __construct(
        public readonly string $directory
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return null;
    }

    public function exec(mixed $prep_res): mixed
    {
        return null;
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['directory'] = $this->directory;
        return 'find-composer';
    }
}
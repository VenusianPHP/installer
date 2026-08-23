<?php

namespace Venusian\Installer\Workflows\Misc;

use ProjectSaturnStudios\PocketFlow\Node;
use Symfony\Component\Process\ExecutableFinder;

class ComposerFinderNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        return null;
    }

    public function exec(mixed $prep_res): mixed
    {
        return new ExecutableFinder()->find('composer');
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(!is_null($exec_res)) {
            $shared['composer_binary'] = $exec_res;
            return 'default';
        }
        else
        {
            $shared['success'] = false;
            $shared['errors'] = [
                'composer' => "Could not find the composer executable."
            ];
            return null;
        }
    }
}
<?php

namespace Venusian\Installer\Actions;

use Symfony\Component\Filesystem\Filesystem;

class ResolveDirectory
{

    public static function run(string $name): string
    {
        if ($name === '.') {
            return '.';
        }

        if ((new Filesystem)->isAbsolutePath($name)) {
            return $name;
        }

        $cwd = getcwd();

        if ($cwd === false) {
            return $name;
        }

        return $cwd.DIRECTORY_SEPARATOR.$name;
    }
}
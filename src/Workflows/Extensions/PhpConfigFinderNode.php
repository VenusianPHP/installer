<?php

namespace Venusian\Installer\Workflows\Extensions;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;

/**
 * Finds the php-config that builds for the PHP binary running the installer,
 * so PIE builds for that binary and no other.
 */
class PhpConfigFinderNode extends Node
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
     * Tries the php-config beside the binary (php8.4 → php-config8.4), then the
     * one on PATH. Each says which binary it builds for.
     *
     * @return array{php_config: ?string, other: ?string} `other` is the binary a
     *                                                     php-config builds for when none builds for this one.
     */
    public function exec(mixed $prep_res): mixed
    {
        $php = $this->host->phpBinary();
        $beside = dirname($php).'/'.preg_replace('/php/', 'php-config', basename($php), 1);
        $other = null;

        foreach (array_unique(array_filter([$beside, $this->host->find('php-config')])) as $candidate) {
            $builds_for = $this->runner->output([$candidate, '--php-binary']);

            if(is_null($builds_for) || $builds_for === '') {
                continue;
            }

            if($this->resolved($builds_for) === $this->resolved($php)) {
                return ['php_config' => $candidate, 'other' => null];
            }

            $other = $builds_for;
        }

        return ['php_config' => null, 'other' => $other];
    }

    /**
     * With no php-config on the machine the flow goes on: PIE checks for build
     * tools itself and offers to install PHP's development files.
     */
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(is_null($exec_res['php_config']) && !is_null($exec_res['other'])) {
            $php = $this->host->phpBinary();
            $shared['extensions_note'] = "The php-config on this machine builds for {$exec_res['other']}, not for {$php}. Install the development files for {$php} first.";
            return null;
        }

        $shared['php_config'] = $exec_res['php_config'];
        return 'find-pie';
    }

    private function resolved(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }
}

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
     * Tries the php-config in the directory this PHP was configured to
     * install into, then the one beside the binary (php8.4 → php-config8.4),
     * then the one on PATH.
     *
     * @return ?string The php-config that builds for this PHP, or null.
     */
    public function exec(mixed $prep_res): mixed
    {
        $php = $this->host->phpBinary();
        $candidates = array_unique(array_filter([
            $this->host->phpBindir().'/php-config',
            dirname($php).'/'.preg_replace('/php/', 'php-config', basename($php), 1),
            $this->host->find('php-config'),
        ]));

        foreach ($candidates as $candidate) {
            if ($this->buildsForThisPhp($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * With none found, no php-config is passed: PIE, which runs under this
     * PHP, targets the PHP running it, checks for build tools itself and
     * offers to install PHP's development files.
     */
    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['php_config'] = $exec_res;

        return 'find-pie';
    }

    /**
     * A php-config builds for this PHP when it names this binary, or, when it
     * names none (a build without the CLI SAPI in its list prints "NONE"),
     * when its prefix, version and thread safety are this PHP's.
     */
    private function buildsForThisPhp(string $php_config): bool
    {
        $builds_for = $this->runner->output([$php_config, '--php-binary']);

        if (is_null($builds_for)) {
            return false;
        }

        if ($builds_for !== '' && $builds_for !== 'NONE') {
            return $this->resolved($builds_for) === $this->resolved($this->host->phpBinary());
        }

        $prefix = $this->runner->output([$php_config, '--prefix']);
        $vernum = $this->runner->output([$php_config, '--vernum']);
        $extension_dir = $this->runner->output([$php_config, '--extension-dir']);

        return ! is_null($prefix) && ! is_null($vernum) && ! is_null($extension_dir)
            && $this->resolved($prefix) === $this->resolved($this->host->phpPrefix())
            && (int) $vernum === $this->host->phpVersionId()
            && str_contains($extension_dir, 'zts') === $this->host->threadSafe();
    }

    private function resolved(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }
}

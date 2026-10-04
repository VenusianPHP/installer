<?php

namespace Venusian\Installer\Workflows\Extensions;

use ProjectSaturnStudios\PocketFlow\Node;
use Throwable;
use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;

class ExtensionInstallNode extends Node
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
            'selected' => $shared['extensions_selected'],
            'tokens' => $shared['extension_tokens'] ?? [],
            'pie_binary' => $shared['pie_binary'],
            'php_config' => $shared['php_config'] ?? null,
            'output_callback' => $shared['output_callback'] ?? null,
        ];
    }

    /**
     * One `pie install` per extension, run by the PHP binary running the
     * installer and on its terminal, so PIE can ask for a sudo password or for
     * missing build tools. A failed build, or a PIE that cannot be started,
     * does not stop the ones after it.
     * Each install is then checked by asking that binary for the extension.
     *
     * @return array<string, string> Extension name => outcome.
     */
    public function exec(mixed $prep_res): mixed
    {
        $php = $this->host->phpBinary();
        $results = [];

        foreach ($prep_res['selected'] as $package) {
            $name = FirstPartyExtension::from($package)->extension();

            $command = [$php, $prep_res['pie_binary'], 'install', $prep_res['tokens'][$package] ?? $package];
            if(!is_null($prep_res['php_config'])) {
                $command[] = '--with-php-config='.$prep_res['php_config'];
            }

            try {
                $exit = $this->runner->run($command, null, $prep_res['output_callback'], true);

                $results[$name] = match (true) {
                    $exit !== 0 => "failed (PIE exit code {$exit})",
                    !$this->runner->succeeds([$php, '--ri', $name]) => "installed by PIE, but {$php} does not load it",
                    str_ends_with($prep_res['tokens'][$package] ?? '', '-dev') => 'installed ('.explode(':', $prep_res['tokens'][$package])[1].')',
                    default => 'installed',
                };
            } catch (Throwable $e) {
                $results[$name] = "failed ({$e->getMessage()})";
            }
        }

        return $results;
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['extension_results'] = $exec_res;
        return null;
    }
}

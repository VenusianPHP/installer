<?php

namespace Venusian\Installer\Workflows\Topside;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Enums\Toolkit;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;
use Venusian\Installer\Workflows\Extensions\ExtensionsFlow;

/**
 * Installs the selected toolkits: first the extensions they run on, through
 * the extension flow with no list (one already loaded is left alone), then
 * each adapter with `composer require`. An adapter is required only once
 * every extension it needs loads in the PHP binary running the installer,
 * since its composer.json requires them.
 */
class ToolkitInstallNode extends Node
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
            'selected' => $shared['toolkits_selected'],
            'composer' => $shared['composer_command'],
            'directory' => $shared['directory'],
            'interactive' => $shared['interactive'] ?? false,
            'output_callback' => $shared['output_callback'] ?? null,
        ];
    }

    /**
     * @return array{results: array<string, string>, extensions: array<string, mixed>}
     *         Toolkit name => outcome, and the extension flow's bag.
     */
    public function exec(mixed $prep_res): mixed
    {
        $os = $this->host->osFamily();
        $toolkits = array_map(fn (string $value): Toolkit => Toolkit::from($value), $prep_res['selected']);

        $catalog = [];
        foreach ($toolkits as $toolkit) {
            foreach ($toolkit->extensionsOn($os) as $extension) {
                if(!in_array($extension, $catalog, true)) {
                    $catalog[] = $extension;
                }
            }
        }

        $extensions = [
            'interactive' => $prep_res['interactive'],
            'output_callback' => $prep_res['output_callback'],
        ];
        (new ExtensionsFlow($this->host, $this->runner, $catalog, ask: false, pick: false))->run($extensions);

        $php = $this->host->phpBinary();
        $results = [];

        foreach ($toolkits as $toolkit) {
            $missing = array_values(array_filter(
                $toolkit->extensionsOn($os),
                fn (FirstPartyExtension $extension): bool => !$this->runner->succeeds([$php, '--ri', $extension->extension()]),
            ));

            if($missing !== []) {
                $name = $missing[0]->extension();
                $why = $extensions['extension_results'][$name] ?? $extensions['extensions_note'] ?? 'not loaded';
                $results[$toolkit->key()] = "not installed: ext-{$name} ({$why})";

                continue;
            }

            $exit = $this->runner->run(
                [...$prep_res['composer'], 'require', $toolkit->value, '--no-interaction'],
                $prep_res['directory'],
                $prep_res['output_callback'],
            );

            $results[$toolkit->key()] = $exit === 0
                ? 'installed'
                : "not installed: composer require {$toolkit->package()} failed (exit code {$exit})";
        }

        return ['results' => $results, 'extensions' => $extensions];
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['toolkit_results'] = $exec_res['results'];

        return null;
    }
}

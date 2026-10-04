<?php

namespace Venusian\Installer\Workflows\Extensions;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Prompts\ChecklistPrompt;

class ExtensionSelectNode extends Node
{
    /**
     * @param  bool  $preselect  Start with every installable row selected.
     */
    public function __construct(
        private bool $preselect = true,
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return [
            'states' => $shared['extension_states'],
            'selected' => $shared['extensions_selected'] ?? null,
            'tokens' => $shared['extension_tokens'] ?? [],
        ];
    }

    /**
     * Every extension is listed. One that cannot be installed here is a
     * disabled row with its reason; one that installs from its development
     * branch says so. An extension named up front is the
     * selection, and nothing is asked.
     *
     * @return list<string> The selected FirstPartyExtension values.
     */
    public function exec(mixed $prep_res): mixed
    {
        if(!is_null($prep_res['selected'])) {
            return $prep_res['selected'];
        }

        $options = [];
        foreach (array_keys($prep_res['states']) as $package) {
            $extension = FirstPartyExtension::from($package);
            $development = ($prep_res['tokens'][$package] ?? null) === $extension->developmentToken();
            $options[$package] = "{$extension->extension()} — {$extension->description()}".($development ? " [{$extension->line()}.x-dev]" : '');
        }

        $disabled = array_filter($prep_res['states'], fn (?string $reason): bool => !is_null($reason));

        return (new ChecklistPrompt(
            label: 'Extensions to install',
            options: $options,
            disabled: $disabled,
            default: $this->preselect ? array_keys(array_diff_key($options, $disabled)) : [],
            scroll: count($options),
            hint: 'Space to select, Enter to confirm.',
        ))->prompt();
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if($exec_res === []) {
            $shared['extensions_note'] = 'None selected.';
            return null;
        }

        $shared['extensions_selected'] = $exec_res;
        return 'install-extensions';
    }
}

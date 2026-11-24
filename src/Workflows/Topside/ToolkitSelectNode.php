<?php

namespace Venusian\Installer\Workflows\Topside;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\Toolkit;
use Venusian\Installer\Host;
use Venusian\Installer\Prompts\ChecklistPrompt;

/**
 * Lists the four toolkits. One this operating system cannot have is a
 * disabled row with its reason. Nothing starts selected, and selecting none
 * is an answer.
 */
class ToolkitSelectNode extends Node
{
    public function __construct(
        private Host $host = new Host,
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return $this->host->osFamily();
    }

    /**
     * @return list<string> The selected Toolkit values.
     */
    public function exec(mixed $prep_res): mixed
    {
        $options = [];
        $disabled = [];

        foreach (Toolkit::cases() as $toolkit) {
            $options[$toolkit->value] = "{$toolkit->key()} — {$toolkit->description()}";

            $reason = $toolkit->unsupportedOn($prep_res);
            if(!is_null($reason)) {
                $disabled[$toolkit->value] = $reason;
            }
        }

        return (new ChecklistPrompt(
            label: 'Toolkit SDKs to install',
            options: $options,
            disabled: $disabled,
            scroll: count($options),
            hint: 'Space to select, Enter to confirm. Selecting none is fine.',
        ))->prompt();
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if($exec_res === []) {
            $shared['toolkits_note'] = 'None selected.';

            return null;
        }

        $shared['toolkits_selected'] = $exec_res;

        return 'install-toolkits';
    }
}

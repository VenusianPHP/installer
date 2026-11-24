<?php

namespace Venusian\Installer\Workflows\Topside;

use ProjectSaturnStudios\PocketFlow\Node;
use function Laravel\Prompts\confirm;

/**
 * Asks whether to install toolkit SDKs: a toolkit's PHP extension and its
 * jovian/venusian- adapter.
 */
class ToolkitOfferNode extends Node
{
    public function prep(mixed &$shared): mixed
    {
        return $shared['interactive'] ?? false;
    }

    /**
     * @return ?string Why the step ends here, or null to go on.
     */
    public function exec(mixed $prep_res): mixed
    {
        if(!$prep_res) {
            return 'Not offered: this run is not interactive.';
        }

        $accepted = confirm(
            label: 'Install toolkit SDKs?',
            hint: 'Each is a PHP extension, built by PIE, and its jovian/venusian- adapter.',
        );

        return $accepted ? null : 'Declined.';
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(!is_null($exec_res)) {
            $shared['toolkits_note'] = $exec_res;

            return null;
        }

        return 'select-toolkits';
    }
}

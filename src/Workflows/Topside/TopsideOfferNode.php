<?php

namespace Venusian\Installer\Workflows\Topside;

use ProjectSaturnStudios\PocketFlow\Node;
use function Laravel\Prompts\confirm;

/**
 * Asks whether to add venusian/topside to the new application.
 */
class TopsideOfferNode extends Node
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
            label: 'Install venusian/topside?',
            hint: 'Windows, native widgets and views. Its config is published into config/.',
        );

        return $accepted ? null : 'Declined.';
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(!is_null($exec_res)) {
            $shared['topside_note'] = $exec_res;

            return null;
        }

        return 'install-topside';
    }
}

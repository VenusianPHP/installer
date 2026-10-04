<?php

namespace Venusian\Installer\Workflows\Extensions;

use ProjectSaturnStudios\PocketFlow\Flow;
use Venusian\Installer\Host;
use Throwable;
use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\ProcessRunner;

/**
 * Offers the first-party PHP extensions and installs the chosen ones through
 * PIE. A flow of its own, so it can run as one node of a larger flow.
 *
 * Reads from the bag: `interactive`, `output_callback`, and `extension_only`
 * (an extension name: install that one, show no list).
 *
 * Every transition has a name. A node stops the flow by returning null, which
 * PocketFlow reads as "default", and no node here has a default successor.
 *
 * The step is optional, so nothing it throws leaves it: the error becomes the
 * step's note and the flow that ran it carries on.
 */
class ExtensionsFlow extends Flow
{
    /**
     * @param  ?list<FirstPartyExtension>  $catalog  The extensions on offer; the ones `new` offers when null.
     * @param  bool  $ask  Ask before showing the list.
     * @param  bool  $preselect  Start the list with every installable row selected.
     */
    public function __construct(
        Host $host = new Host,
        ProcessRunner $runner = new ProcessRunner,
        ?array $catalog = null,
        bool $ask = true,
        bool $preselect = true,
    ) {
        $offer_node = new ExtensionOfferNode($host, $catalog, $ask);
        $php_config_node = new PhpConfigFinderNode($host, $runner);
        $pie_finder_node = new PieFinderNode($host, $runner);
        $pie_install_node = new PieInstallNode($host, $runner);
        $select_node = new ExtensionSelectNode($preselect);
        $install_node = new ExtensionInstallNode($host, $runner);

        $offer_node->next($php_config_node, 'find-php-config');
        $php_config_node->next($pie_finder_node, 'find-pie');
        $pie_finder_node->next($select_node, 'select-extensions');
        $pie_finder_node->next($pie_install_node, 'install-pie');
        $pie_install_node->next($select_node, 'select-extensions');
        $select_node->next($install_node, 'install-extensions');

        parent::__construct($offer_node);
    }

    public function _run(mixed &$shared): mixed
    {
        try {
            return parent::_run($shared);
        } catch (Throwable $e) {
            $shared['extensions_note'] = "Stopped by an error: {$e->getMessage()}";

            return null;
        }
    }
}

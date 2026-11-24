<?php

namespace Venusian\Installer\Workflows\NewApplication;

use ProjectSaturnStudios\PocketFlow\Flow;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;
use Venusian\Installer\Workflows\Extensions\ExtensionsFlow;
use Venusian\Installer\Workflows\Misc\ComposerFinderNode;
use Venusian\Installer\Workflows\Topside\TopsideInstallNode;
use Venusian\Installer\Workflows\Topside\TopsideOfferNode;
use Venusian\Installer\Workflows\Topside\ToolkitInstallNode;
use Venusian\Installer\Workflows\Topside\ToolkitOfferNode;
use Venusian\Installer\Workflows\Topside\ToolkitSelectNode;

/**
 * `new`: Start → ComposerFinder → ProjectCreation → ExtensionsFlow →
 * TopsideOffer → TopsideInstall → ToolkitOffer → ToolkitSelect →
 * ToolkitInstall. Everything after ProjectCreation is optional: each step
 * ends with a note in the bag, and the application stays.
 */
class NewApplicationFlow extends Flow
{
    public function __construct(
        string $directory,
        Host $host = new Host,
        ProcessRunner $runner = new ProcessRunner,
    ) {
        $start_node = new NewApplicationStartNode($directory);
        $finder_node = new ComposerFinderNode($host);
        $creation_node = new ProjectCreationNode($runner);
        $extensions_flow = new ExtensionsFlow($host, $runner, then: 'offer-topside');
        $topside_offer_node = new TopsideOfferNode;
        $topside_install_node = new TopsideInstallNode($host, $runner);
        $toolkit_offer_node = new ToolkitOfferNode;
        $toolkit_select_node = new ToolkitSelectNode($host);
        $toolkit_install_node = new ToolkitInstallNode($host, $runner);

        $start_node->next($finder_node, 'find-composer');
        $finder_node->next($creation_node, 'create-project');
        $creation_node->next($extensions_flow, 'offer-extensions');
        $extensions_flow->next($topside_offer_node, 'offer-topside');
        $topside_offer_node->next($topside_install_node, 'install-topside');
        $topside_install_node->next($toolkit_offer_node, 'offer-toolkits');
        $toolkit_offer_node->next($toolkit_select_node, 'select-toolkits');
        $toolkit_select_node->next($toolkit_install_node, 'install-toolkits');

        parent::__construct($start_node);
    }
}

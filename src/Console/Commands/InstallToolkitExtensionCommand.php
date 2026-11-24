<?php

namespace Venusian\Installer\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Venusian\Installer\Enums\FirstPartyExtension;

/**
 * install:ext over the extensions Topside's toolkits run on: appkit, gtk,
 * qt and raven.
 */
#[AsCommand(
    name: 'install:toolkit-ext',
    description: 'Install the PHP extensions Topside\'s toolkits run on through PIE',
)]
class InstallToolkitExtensionCommand extends InstallExtensionCommand
{
    public static function catalog(): array
    {
        return FirstPartyExtension::toolkits();
    }
}

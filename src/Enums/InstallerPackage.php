<?php

namespace Venusian\Installer\Enums;

enum InstallerPackage: string
{
    case COMPOSER = 'venusian/installer';

    case BINARY = 'venusian';

    case PACKAGIST_P2_URL = 'https://repo.packagist.org/p2/venusian/installer.json';

    case USER_AGENT = 'ScrapyardIO Installer';

    case CACHE_BODY_FILENAME = 'venusian-installer-version-check.json';

    case CACHE_LAST_MODIFIED_FILENAME = 'venusian-installer-last-modified';

    case NO_UPDATE_CHECK_ENV = 'VENUSIAN_INSTALLER_NO_UPDATE_CHECK';
}

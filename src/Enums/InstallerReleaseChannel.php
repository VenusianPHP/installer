<?php

namespace Venusian\Installer\Enums;

enum InstallerReleaseChannel: int
{
    case CACHE_TTL_SECONDS = 86400;

    case HTTP_TIMEOUT_SECONDS = 3;
}

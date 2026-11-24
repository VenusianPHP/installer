<?php

namespace Venusian\Installer\Enums;

enum SkeletonPackage: string
{
    case VENUSIAN = 'venusian/venusian:^0.11.1';

    /** The skeleton's console entry point, run with `php`. */
    case CONSOLE = 'computer';
}

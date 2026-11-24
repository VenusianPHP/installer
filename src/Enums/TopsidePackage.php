<?php

namespace Venusian\Installer\Enums;

enum TopsidePackage: string
{
    /** What `composer require` takes. */
    case COMPOSER = 'venusian/topside:^0.11';

    /** The vendor:publish tag of Topside's config files. */
    case CONFIG_TAG = 'surface-config';
}

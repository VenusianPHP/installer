<?php

namespace Venusian\Installer\Exceptions;

use RuntimeException;

class ComposerNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Composer could not be found. Install Composer and ensure it is available on your PATH: https://getcomposer.org/download/'
        );
    }
}

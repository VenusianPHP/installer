<?php

use Venusian\Installer\Enums\InstallerPackage;
use Venusian\Installer\Enums\InstallerReleaseChannel;
use Venusian\Installer\Enums\SkeletonPackage;

it('pins SkeletonPackage::VENUSIAN to venusian/venusian:^0.8.2', function () {
    expect(SkeletonPackage::VENUSIAN->value)->toBe('venusian/venusian:^0.8.2');
});

it('exposes every InstallerPackage case value including leftover branding', function () {
    expect(InstallerPackage::COMPOSER->value)->toBe('venusian/installer')
        ->and(InstallerPackage::BINARY->value)->toBe('venusian')
        ->and(InstallerPackage::PACKAGIST_P2_URL->value)->toBe('https://repo.packagist.org/p2/venusian/installer.json')
        ->and(InstallerPackage::USER_AGENT->value)->toBe('ScrapyardIO Installer')
        ->and(InstallerPackage::CACHE_BODY_FILENAME->value)->toBe('venusian-installer-version-check.json')
        ->and(InstallerPackage::CACHE_LAST_MODIFIED_FILENAME->value)->toBe('venusian-installer-last-modified')
        ->and(InstallerPackage::NO_UPDATE_CHECK_ENV->value)->toBe('VENUSIAN_INSTALLER_NO_UPDATE_CHECK');
});

it('exposes InstallerReleaseChannel integer cases', function () {
    expect(InstallerReleaseChannel::CACHE_TTL_SECONDS->value)->toBe(86400)
        ->and(InstallerReleaseChannel::HTTP_TIMEOUT_SECONDS->value)->toBe(3);
});

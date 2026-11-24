<?php

use Venusian\Installer\Enums\InstallerPackage;
use Venusian\Installer\Enums\InstallerReleaseChannel;
use Venusian\Installer\Enums\SkeletonPackage;
use Venusian\Installer\Enums\TopsidePackage;

it('pins SkeletonPackage::VENUSIAN to venusian/venusian:^0.11.1 and names its console', function () {
    expect(SkeletonPackage::VENUSIAN->value)->toBe('venusian/venusian:^0.11.1')
        ->and(SkeletonPackage::CONSOLE->value)->toBe('computer');
});

it('pins Topside to its 0.11 line and names its config tag', function () {
    expect(TopsidePackage::COMPOSER->value)->toBe('venusian/topside:^0.11')
        ->and(TopsidePackage::CONFIG_TAG->value)->toBe('surface-config');
});

it('exposes every InstallerPackage case value', function () {
    expect(array_map(fn (InstallerPackage $case): string => $case->value, InstallerPackage::cases()))->toBe([
        'venusian/installer',
        'venusian',
        'https://repo.packagist.org/p2/venusian/installer.json',
        'Venusian Installer',
        'venusian-installer-version-check.json',
        'venusian-installer-last-modified',
        'VENUSIAN_INSTALLER_NO_UPDATE_CHECK',
    ]);
});

it('exposes InstallerReleaseChannel integer cases', function () {
    expect(InstallerReleaseChannel::CACHE_TTL_SECONDS->value)->toBe(86400)
        ->and(InstallerReleaseChannel::HTTP_TIMEOUT_SECONDS->value)->toBe(3);
});

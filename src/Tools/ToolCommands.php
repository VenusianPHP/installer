<?php

namespace Venusian\Installer\Tools;

use Closure;
use Composer\InstalledVersions;

/**
 * Command classes that installed venusian-tool packages declare under
 * extra.venusian.commands. venusian/build is one: install:sdk requires it,
 * and bin/venusian adds whatever it declares.
 */
final class ToolCommands
{
    public const TYPE = 'venusian-tool';

    /**
     * @param  Closure(string): list<string>|null  $packages_of_type  package names of a Composer type
     * @param  Closure(string): (string|null)|null  $install_path  a package's install path
     * @return list<class-string>
     */
    public static function fromInstalled(?Closure $packages_of_type = null, ?Closure $install_path = null): array
    {
        $packages_of_type ??= fn (string $type): array => InstalledVersions::getInstalledPackagesByType($type);
        $install_path ??= fn (string $package): ?string => InstalledVersions::getInstallPath($package);
        $packages = [];

        foreach ($packages_of_type(self::TYPE) as $name) {
            $path = $install_path($name);
            $manifest = is_string($path) && is_file($path.'/composer.json')
                ? json_decode((string) file_get_contents($path.'/composer.json'), true)
                : null;
            $packages[$name] = is_array($manifest) ? (array) ($manifest['extra'] ?? []) : [];
        }

        return self::fromPackages($packages);
    }

    /**
     * @param  array<string, array<string, mixed>>  $packages  name => composer extra
     * @return list<class-string>
     */
    public static function fromPackages(array $packages): array
    {
        $classes = [];

        foreach ($packages as $extra) {
            $declared = $extra['venusian']['commands'] ?? [];

            foreach (is_array($declared) ? $declared : [] as $class) {
                if (is_string($class) && $class !== '') {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }
}

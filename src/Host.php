<?php

namespace Venusian\Installer;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Venusian\Installer\Enums\InstallerPackage;
use Venusian\Installer\Enums\InstallerReleaseChannel;

/**
 * The machine the installer runs on, and the PHP binary running it.
 */
class Host
{
    public function phpBinary(): string
    {
        return PHP_BINARY;
    }

    /** A PHP_OS_FAMILY value. */
    public function osFamily(): string
    {
        return PHP_OS_FAMILY;
    }

    /** Whether the running PHP binary has the extension loaded. */
    public function loaded(string $extension): bool
    {
        return extension_loaded($extension);
    }

    public function home(): ?string
    {
        $home = getenv('HOME');

        return is_string($home) && $home !== '' ? $home : null;
    }

    /**
     * @param  list<string>  $extra_directories  Searched after PATH.
     */
    public function find(string $name, array $extra_directories = []): ?string
    {
        return (new ExecutableFinder)->find($name, null, $extra_directories);
    }

    /** Fetches a URL into an executable file, creating its directory. */
    public function download(string $url, string $path): bool
    {
        $filesystem = new Filesystem;

        try {
            $filesystem->mkdir(dirname($path));
            $filesystem->copy($url, $path, true);
            $filesystem->chmod($path, 0755);
        } catch (IOExceptionInterface) {
            $filesystem->remove($path);

            return false;
        }

        return true;
    }

    /**
     * The versions Packagist lists for a package, tagged releases then
     * development branches ("0.10.x-dev"), without a leading "v". Empty when
     * it lists none; null when Packagist could not be asked.
     *
     * @return ?list<string>
     */
    public function releases(string $package): ?array
    {
        $tagged = $this->packagistVersions($package, $package);
        $branches = $this->packagistVersions($package, "{$package}~dev");

        return is_null($tagged) || is_null($branches) ? null : [...$tagged, ...$branches];
    }

    /**
     * @return ?list<string>
     */
    private function packagistVersions(string $package, string $file): ?array
    {
        $context = stream_context_create(['http' => [
            'timeout' => InstallerReleaseChannel::HTTP_TIMEOUT_SECONDS->value,
            'ignore_errors' => true,
            'header' => 'User-Agent: '.InstallerPackage::USER_AGENT->value,
        ]]);

        $body = @file_get_contents("https://repo.packagist.org/p2/{$file}.json", false, $context);
        if ($body === false) {
            return null;
        }

        $status = (int) (explode(' ', $http_response_header[0] ?? '')[1] ?? 0);
        if ($status === 404) {
            return [];
        }

        $versions = json_decode($body, true)['packages'][$package] ?? null;
        if ($status !== 200 || ! is_array($versions)) {
            return null;
        }

        return array_map(fn (array $version): string => ltrim((string) $version['version'], 'v'), array_values($versions));
    }

    public function remove(string $path): void
    {
        (new Filesystem)->remove($path);
    }
}

<?php

namespace Venusian\Installer\Tests\Fakes;

use Venusian\Installer\Host;

/**
 * A machine described by its properties: which PHP runs, which extensions it
 * has loaded, which executables exist, and whether a download arrives.
 */
class FakeHost extends Host
{
    /** @var list<array{string, string}> */
    public array $downloads = [];

    /** @var list<string> */
    public array $removed = [];

    /** @var list<array{string, list<string>}> */
    public array $searches = [];

    /**
     * @param  list<string>  $loaded  Extension names.
     * @param  array<string, string>  $executables  Name => path.
     * @param  list<string>  $unreleased  Packagist package names.
     * @param  list<string>  $absent  Packagist package names.
     */
    public function __construct(
        public string $php = '/opt/php/bin/php',
        public string $os = 'Darwin',
        public array $loaded = [],
        public ?string $home = '/home/dev',
        public array $executables = [],
        public bool $downloads_arrive = true,
        public array $unreleased = [],
        public array $absent = [],
        public bool $packagist_answers = true,
    ) {}

    /**
     * Every package has a 0.10.0 release, unless named in $unreleased (an old
     * tag and the 0.10 development branch) or in $absent (not on Packagist).
     */
    public function releases(string $package): ?array
    {
        if (! $this->packagist_answers) {
            return null;
        }

        if (in_array($package, $this->absent, true)) {
            return [];
        }

        return in_array($package, $this->unreleased, true) ? ['0.8.0', '0.10.x-dev', 'dev-main'] : ['0.10.0', '0.9.0', '0.10.x-dev', 'dev-main'];
    }

    public function phpBinary(): string
    {
        return $this->php;
    }

    public function osFamily(): string
    {
        return $this->os;
    }

    public function loaded(string $extension): bool
    {
        return in_array($extension, $this->loaded, true);
    }

    public function home(): ?string
    {
        return $this->home;
    }

    public function find(string $name, array $extra_directories = []): ?string
    {
        $this->searches[] = [$name, $extra_directories];

        return $this->executables[$name] ?? null;
    }

    public function download(string $url, string $path): bool
    {
        $this->downloads[] = [$url, $path];

        return $this->downloads_arrive;
    }

    public function remove(string $path): void
    {
        $this->removed[] = $path;
    }
}

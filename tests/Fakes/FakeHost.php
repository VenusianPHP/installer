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
        public string $bindir = '/opt/php/bin',
        public string $prefix = '/opt/php',
        public int $version_id = 80427,
        public bool $zts = false,
    ) {}

    /**
     * Every package has a 0.10.0 release, unless named in $unreleased (an old
     * tag and the 0.11 development branch) or in $absent (not on Packagist).
     */
    public function releases(string $package): ?array
    {
        if (! $this->packagist_answers) {
            return null;
        }

        if (in_array($package, $this->absent, true)) {
            return [];
        }

        return in_array($package, $this->unreleased, true) ? ['0.8.0', '0.11.x-dev', 'dev-main'] : ['0.11.0', '0.10.0', '0.11.x-dev', 'dev-main'];
    }

    public function phpBinary(): string
    {
        return $this->php;
    }

    public function phpBindir(): string
    {
        return $this->bindir;
    }

    public function phpPrefix(): string
    {
        return $this->prefix;
    }

    public function phpVersionId(): int
    {
        return $this->version_id;
    }

    public function threadSafe(): bool
    {
        return $this->zts;
    }

    /** Every script here is a PHP script. */
    public function phpCommand(string $script): array
    {
        return [$this->php, $script];
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

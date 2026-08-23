<?php

namespace Venusian\Installer\ReleaseChannel;

use Composer\InstalledVersions;
use Venusian\Installer\Enums\InstallerPackage;
use Venusian\Installer\Enums\InstallerReleaseChannel;
use Venusian\Installer\ProcessRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

use function Laravel\Prompts\confirm;

class PackagistReleaseWatcher
{
    /**
     * @param  callable(string, list<string>): (array{status: int, headers: string, body: string}|false)|null  $httpClient
     * @param  callable(): int|null  $clock
     * @param  callable(string): bool|null  $confirm
     * @param  callable(): string|null  $installedVersionResolver
     * @param  callable(list<string>): int|null  $reExec
     * @param  callable(int): void|null  $terminate
     * @param  callable(): (?string)|null  $composerFinder
     * @param  callable(): (?string)|null  $binaryFinder
     */
    public function __construct(
        private ProcessRunner $processRunner = new ProcessRunner,
        private $httpClient = null,
        private $clock = null,
        private ?string $cacheDirectory = null,
        private $confirm = null,
        private $installedVersionResolver = null,
        private $reExec = null,
        private $terminate = null,
        private $composerFinder = null,
        private $binaryFinder = null,
    ) {
        $finder = new ExecutableFinder;

        $this->httpClient ??= $this->defaultHttpClient(...);
        $this->clock ??= static fn (): int => time();
        $this->cacheDirectory ??= sys_get_temp_dir();
        $this->confirm ??= static fn (string $label): bool => confirm(label: $label);
        $this->installedVersionResolver ??= $this->resolveInstalledVersion(...);
        $this->reExec ??= $this->defaultReExec(...);
        $this->terminate ??= static function (int $code): void {
            exit($code);
        };
        $this->composerFinder ??= static fn (): ?string => $finder->find('composer');
        $this->binaryFinder ??= static fn (): ?string => $finder->find(InstallerPackage::BINARY->value);
    }

    /**
     * @param  list<string>  $argv
     */
    public function maybeOfferUpdate(
        InputInterface $input,
        OutputInterface $output,
        array $argv,
        ?string $fallbackVersion = null,
    ): void {
        if ($this->shouldSkip($input, $argv)) {
            return;
        }

        $resolved = ($this->installedVersionResolver)();
        if ($resolved === '' && is_string($fallbackVersion) && $fallbackVersion !== '') {
            $resolved = $fallbackVersion;
        }

        $installed = $this->normalizeVersion($resolved);
        if (! $this->isComparableStableVersion($installed)) {
            return;
        }

        $latest = $this->fetchLatestVersion();
        if (is_null($latest)) {
            return;
        }

        if (version_compare($installed, $latest) !== -1) {
            return;
        }

        $output->writeln('');
        $output->writeln(
            "  <bg=yellow;fg=black> WARN </> A new version of the Venusian installer is available."
            ." You have version {$installed} installed, the latest version is {$latest}."
        );
        $output->writeln('');

        if (! ($this->confirm)('Would you like to update now?')) {
            return;
        }

        $composer = ($this->composerFinder)();
        if (is_null($composer) || $composer === '') {
            $output->writeln('<error>Could not find composer on PATH; update skipped.</error>');

            return;
        }

        // `require` rewrites the global constraint (update cannot widen ^0.6 → ^0.7).
        $exitCode = $this->processRunner->run(
            [
                $composer,
                'global',
                'require',
                $this->requireConstraint($latest),
                '--with-all-dependencies',
                '--no-interaction',
            ],
            inheritTty: true,
        );

        if ($exitCode !== 0) {
            $output->writeln('');
            $output->writeln('<error>Installer update failed; continuing with the current version.</error>');

            return;
        }

        $binary = ($this->binaryFinder)();
        if (is_null($binary) || $binary === '') {
            $output->writeln('');
            $output->writeln('<error>Updated, but could not find scrapyard-io on PATH. Re-run your command manually.</error>');

            return;
        }

        $command = array_merge([$binary], array_slice($argv, 1));
        $output->writeln('');
        ($this->terminate)(($this->reExec)($command));
    }

    /**
     * @param  list<string>  $argv
     */
    private function shouldSkip(InputInterface $input, array $argv): bool
    {
        $envName = InstallerPackage::NO_UPDATE_CHECK_ENV->value;
        $disabled = getenv($envName);
        if ($disabled === '1' || $disabled === 'true') {
            return true;
        }

        if (! $input->isInteractive()) {
            return true;
        }

        $tokens = array_values(array_filter(
            array_slice($argv, 1),
            static fn (string $token): bool => $token !== '' && ! str_starts_with($token, '-'),
        ));

        return ($tokens[0] ?? null) === 'completion';
    }

    private function fetchLatestVersion(): ?string
    {
        $bodyPath = $this->cachePath(InstallerPackage::CACHE_BODY_FILENAME);
        $lastModifiedPath = $this->cachePath(InstallerPackage::CACHE_LAST_MODIFIED_FILENAME);
        $now = ($this->clock)();
        $ttl = InstallerReleaseChannel::CACHE_TTL_SECONDS->value;

        $cacheExists = is_file($bodyPath);
        if ($cacheExists && filemtime($bodyPath) > ($now - $ttl)) {
            return $this->parseLatestVersion((string) file_get_contents($bodyPath));
        }

        $headers = ['User-Agent: '.InstallerPackage::USER_AGENT->value];
        if (is_file($lastModifiedPath)) {
            $lastModified = trim((string) file_get_contents($lastModifiedPath));
            if ($lastModified !== '') {
                $headers[] = "If-Modified-Since: {$lastModified}";
            }
        }

        try {
            $response = ($this->httpClient)(InstallerPackage::PACKAGIST_P2_URL->value, $headers);
        } catch (Throwable) {
            return null;
        }

        if ($response === false) {
            return null;
        }

        $lastModifiedFromResponse = null;
        if (preg_match('/^Last-Modified:\s*(.+)$/mi', $response['headers'], $matches) === 1) {
            $lastModifiedFromResponse = trim($matches[1]);
        }
        file_put_contents($lastModifiedPath, $lastModifiedFromResponse ?? '');

        if ($response['status'] === 304 && $cacheExists) {
            touch($bodyPath);

            return $this->parseLatestVersion((string) file_get_contents($bodyPath));
        }

        if ($response['status'] === 200 && $response['body'] !== '') {
            file_put_contents($bodyPath, $response['body']);

            return $this->parseLatestVersion($response['body']);
        }

        return null;
    }

    private function parseLatestVersion(string $json): ?string
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        $package = InstallerPackage::COMPOSER->value;
        $version = $data['packages'][$package][0]['version'] ?? null;
        if (! is_string($version) || $version === '') {
            return null;
        }

        $normalized = $this->normalizeVersion($version);
        if (! $this->isComparableStableVersion($normalized)) {
            return null;
        }

        return $normalized;
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'v');
    }

    /**
     * Widenable Packagist constraint for `composer global require` (e.g. 0.7.1 → scrapyard-io/installer:^0.7.1).
     */
    private function requireConstraint(string $latest): string
    {
        return InstallerPackage::COMPOSER->value.':^'.$latest;
    }

    private function isComparableStableVersion(string $version): bool
    {
        if ($version === '' || str_contains(strtolower($version), 'dev')) {
            return false;
        }

        return preg_match('/^\d+\.\d+/', $version) === 1;
    }

    private function resolveInstalledVersion(): string
    {
        $package = InstallerPackage::COMPOSER->value;

        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($package)) {
            $pretty = InstalledVersions::getPrettyVersion($package);
            if (is_string($pretty) && $pretty !== '') {
                return $pretty;
            }
        }

        return '';
    }

    private function cachePath(InstallerPackage $filename): string
    {
        return rtrim((string) $this->cacheDirectory, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .$filename->value;
    }

    /**
     * @param  list<string>  $headers
     * @return array{status: int, headers: string, body: string}|false
     */
    private function defaultHttpClient(string $url, array $headers): array|false
    {
        if (function_exists('curl_init')) {
            return $this->httpViaCurl($url, $headers);
        }

        return $this->httpViaStreams($url, $headers);
    }

    /**
     * @param  list<string>  $headers
     * @return array{status: int, headers: string, body: string}|false
     */
    private function httpViaCurl(string $url, array $headers): array|false
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => InstallerReleaseChannel::HTTP_TIMEOUT_SECONDS->value,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        try {
            $response = curl_exec($curl);
            $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $error = curl_error($curl);
        } catch (Throwable) {
            return false;
        } finally {
            if (is_resource($curl) || $curl instanceof \CurlHandle) {
                curl_close($curl);
            }
        }

        if ($error !== '' || $response === false) {
            return false;
        }

        return [
            'status' => $httpCode,
            'headers' => substr((string) $response, 0, $headerSize),
            'body' => substr((string) $response, $headerSize),
        ];
    }

    /**
     * Fallback when php-curl is missing (common on minimal Ubuntu PHP).
     *
     * @param  list<string>  $headers
     * @return array{status: int, headers: string, body: string}|false
     */
    private function httpViaStreams(string $url, array $headers): array|false
    {
        $timeout = InstallerReleaseChannel::HTTP_TIMEOUT_SECONDS->value;
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => $timeout,
                'ignore_errors' => true,
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        try {
            $body = @file_get_contents($url, false, $context);
        } catch (Throwable) {
            return false;
        }

        if ($body === false) {
            return false;
        }

        $responseHeaders = $http_response_header ?? [];
        $statusLine = $responseHeaders[0] ?? 'HTTP/1.1 0';
        $status = 0;
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) === 1) {
            $status = (int) $matches[1];
        }

        return [
            'status' => $status,
            'headers' => implode("\r\n", $responseHeaders)."\r\n\r\n",
            'body' => $body,
        ];
    }

    /**
     * @param  list<string>  $command
     */
    private function defaultReExec(array $command): int
    {
        return $this->processRunner->run($command, inheritTty: true);
    }
}

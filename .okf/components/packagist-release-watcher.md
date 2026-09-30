---
type: Module
title: PackagistReleaseWatcher
description: Offers a global Composer self-update when Packagist is newer than the installed installer.
resource: src/ReleaseChannel/PackagistReleaseWatcher.php
tags: [component, release, packagist]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
status: stable
sources:
  - id: watcher
    resource: src/ReleaseChannel/PackagistReleaseWatcher.php
    title: PackagistReleaseWatcher
  - id: enums
    resource: src/Enums/InstallerPackage.php
    title: InstallerPackage env / URL / cache names
  - id: channel
    resource: src/Enums/InstallerReleaseChannel.php
    title: Cache TTL and HTTP timeout
  - id: app
    resource: src/Console/Application.php
    title: doRun calls maybeOfferUpdate
  - id: watcher-test
    resource: tests/ReleaseChannel/PackagistReleaseWatcherTest.php
    title: Pest coverage for offer / skip / cache TTL
---

# Hook

[Application::doRun](application.md) calls `maybeOfferUpdate($input, $output, $argv, $this->getVersion())` before `parent::doRun`.[^app][^watcher]

All I/O is injectable: `httpClient`, `clock`, `confirm`, `installedVersionResolver`, `reExec`, `terminate`, `composerFinder`, `binaryFinder`, `cacheDirectory`. Tests must inject these — no live Packagist, no real `composer`.[^watcher][^watcher-test]

# Skip rules

`maybeOfferUpdate` returns immediately when:[^watcher]

1. `VENUSIAN_INSTALLER_NO_UPDATE_CHECK` is `1` or `true` ([`InstallerPackage::NO_UPDATE_CHECK_ENV`](enums.md)). Pest covers `1` (`skips the update check when VENUSIAN_INSTALLER_NO_UPDATE_CHECK is 1`); no test for `true`.
2. `$input->isInteractive()` is false. Pest: `skips the update check when the input is non-interactive`.
3. The first non-option argv token after the binary is `completion`. Pest: `skips the update check when the first non-option argv token is completion`.


# Offer path

When installed and latest are comparable stable versions and `version_compare($installed, $latest) === -1`:[^watcher][^watcher-test]

1. Source prints a yellow WARN line with both versions. That string is **not** Pest-asserted.
2. Calls `confirm('Would you like to update now?')` — Pest: `offers an update when latest is greater than installed, then re-execs and terminates`.
3. On yes: `processRunner->run([composer, 'global', 'require', 'venusian/installer:^'.$latest, '--with-all-dependencies', '--no-interaction'], inheritTty: true)`.
4. On require exit `0`: `reExec([$binary, ...array_slice($argv, 1)])` then `terminate($code)`.
5. Require exit non-zero: prints `Installer update failed; continuing with the current version.` and returns. `venusian` not found on `PATH` after the require: prints `Updated, but could not find scrapyard-io on PATH. Re-run your command manually.` and returns. Neither message is Pest-asserted.

No offer when `installed >= latest` — Pest: `does not offer an update when installed is greater than or equal to latest`.[^watcher-test]

# Cache TTL (tested)

`InstallerReleaseChannel::CACHE_TTL_SECONDS` is `86400`. If the body cache file exists and `filemtime > clock() - TTL`, `fetchLatestVersion` parses that cached body and does not call `httpClient`.[^channel][^watcher-test]

# Related

- [Enums](enums.md)
- [ProcessRunner](process-runner.md)

[^watcher]: PackagistReleaseWatcher
[^enums]: InstallerPackage env / URL / cache names
[^channel]: Cache TTL and HTTP timeout
[^app]: doRun calls maybeOfferUpdate
[^watcher-test]: Pest coverage for offer / skip / cache TTL

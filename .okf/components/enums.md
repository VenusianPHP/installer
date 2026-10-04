---
type: Module
title: Enums
description: String- and int-backed enums for skeleton pin, first-party extensions, PIE, Packagist identity, and release-channel numbers.
resource: src/Enums
tags: [component, enum]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
verification_key: "claude-fable-5-1@6689f865c8b40863d13dc520135c0217579d3224"
status: stable
sources:
  - id: skeleton
    resource: src/Enums/SkeletonPackage.php
    title: SkeletonPackage
  - id: package
    resource: src/Enums/InstallerPackage.php
    title: InstallerPackage
  - id: channel
    resource: src/Enums/InstallerReleaseChannel.php
    title: InstallerReleaseChannel
  - id: extension
    resource: src/Enums/FirstPartyExtension.php
    title: FirstPartyExtension
  - id: pie
    resource: src/Enums/PiePackage.php
    title: PiePackage
  - id: enums-test
    resource: tests/Enums/EnumsTest.php
    title: Pest coverage for every case value
  - id: extension-test
    resource: tests/Enums/FirstPartyExtensionTest.php
    title: Pest coverage for FirstPartyExtension and PiePackage
---

# Convention

Backed PHP enums under `src/Enums/`. Cases are FULLY UPPERCASE. No class-level constants for these maps.[^skeleton][^package][^channel][^enums-test]

# SkeletonPackage (`string`)

| Case | Value |
|------|-------|
| `VENUSIAN` | `venusian/venusian:^0.10.0` |

This is the create-project token.[^skeleton][^enums-test]

# FirstPartyExtension (`string`)

Value = the token `pie install` takes: Packagist package + version line.[^extension][^extension-test]

| Case | Value | `extension()` | `unsupportedOn($os_family)` |
|------|-------|---------------|-----------------------------|
| `EPOLL` | `php-io-extensions/epoll:^0.10` | `epoll` | `Linux only` unless `Linux` |
| `KQUEUE` | `php-io-extensions/kqueue:^0.10` | `kqueue` | `macOS only` unless `Darwin` |
| `PCURL` | `php-io-extensions/pcurl:^0.10` | `pcurl` | `not on Windows` on `Windows` |
| `APPKIT` | `php-io-extensions/appkit:^0.10` | `appkit` | `macOS only` unless `Darwin` |
| `GTK`, `QT`, `FB`, `RASTERIZE`, `IMGDEC` | `php-io-extensions/{name}:^0.10` | lowercase case name | `Linux and macOS only` unless `Linux` or `Darwin` |
| `POSI`, `FTDI` | `php-io-extensions/{name}:^0.10` | lowercase case name | `not on Windows` on `Windows` |

`offeredByNew()` = `[EPOLL, KQUEUE, PCURL]`. `named($name)` = case by extension name, case-insensitive, or `null`. `package()` = value without the version line. `line()` = `0.10`. `developmentToken()` = `package:0.10.x-dev`.

`unsupportedOn()` takes a `PHP_OS_FAMILY` value, answers `null` when the OS can have the extension. The families follow each extension's own `php-ext.os-families`. `description()` = the few words shown in the list. The one enum here with methods.

# PiePackage (`string`)

| Case | Value |
|------|-------|
| `BINARY` | `pie` |
| `PHAR_URL` | `https://github.com/php/pie/releases/latest/download/pie.phar` |
| `INSTALL_DIRECTORY` | `.local/bin` (under the home directory) |
| `VERSION_MARKER` | `(PIE)` — text `pie --version` must print |

Used by `PieFinderNode` and `PieInstallNode` ([ExtensionsFlow](/core/extensions-flow.md)).[^pie][^extension-test]

# InstallerPackage (`string`)

| Case | Value |
|------|-------|
| `COMPOSER` | `venusian/installer` |
| `BINARY` | `venusian` |
| `PACKAGIST_P2_URL` | `https://repo.packagist.org/p2/venusian/installer.json` |
| `USER_AGENT` | `ScrapyardIO Installer` |
| `CACHE_BODY_FILENAME` | `venusian-installer-version-check.json` |
| `CACHE_LAST_MODIFIED_FILENAME` | `venusian-installer-last-modified` |
| `NO_UPDATE_CHECK_ENV` | `VENUSIAN_INSTALLER_NO_UPDATE_CHECK` |

`USER_AGENT` is the `User-Agent` header on the Packagist request; `exposes every InstallerPackage case value including leftover branding` asserts it.[^package][^enums-test]

# InstallerReleaseChannel (`int`)

| Case | Value |
|------|-------|
| `CACHE_TTL_SECONDS` | `86400` |
| `HTTP_TIMEOUT_SECONDS` | `3` |

[^skeleton]: SkeletonPackage
[^package]: InstallerPackage
[^channel]: InstallerReleaseChannel
[^enums-test]: Pest coverage for every case value
[^extension]: FirstPartyExtension
[^pie]: PiePackage
[^extension-test]: Pest coverage for FirstPartyExtension and PiePackage

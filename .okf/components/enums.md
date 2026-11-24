---
type: Module
title: Enums
description: String- and int-backed enums for the skeleton, Topside, the first-party extensions, the toolkits, PIE, Packagist identity, and release-channel numbers.
resource: src/Enums
tags: [component, enum]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
sources:
  - id: skeleton
    resource: src/Enums/SkeletonPackage.php
    title: SkeletonPackage
  - id: topside
    resource: src/Enums/TopsidePackage.php
    title: TopsidePackage
  - id: package
    resource: src/Enums/InstallerPackage.php
    title: InstallerPackage
  - id: channel
    resource: src/Enums/InstallerReleaseChannel.php
    title: InstallerReleaseChannel
  - id: extension
    resource: src/Enums/FirstPartyExtension.php
    title: FirstPartyExtension
  - id: toolkit
    resource: src/Enums/Toolkit.php
    title: Toolkit
  - id: pie
    resource: src/Enums/PiePackage.php
    title: PiePackage
  - id: enums-test
    resource: tests/Enums/EnumsTest.php
    title: Pest coverage for the package enums
  - id: extension-test
    resource: tests/Enums/FirstPartyExtensionTest.php
    title: Pest coverage for FirstPartyExtension, Toolkit and PiePackage
---

# Convention

Backed PHP enums under `src/Enums/`. Cases are FULLY UPPERCASE. No class-level constants for these maps.[^enums-test]

# SkeletonPackage (`string`)

| Case | Value |
|------|-------|
| `VENUSIAN` | `venusian/venusian:^0.11.1` — the create-project token |
| `CONSOLE` | `computer` — the skeleton's console, run as `[php, 'computer', …]` |

[^skeleton][^enums-test]

# TopsidePackage (`string`)

| Case | Value |
|------|-------|
| `COMPOSER` | `venusian/topside:^0.11` — what `composer require` takes |
| `CONFIG_TAG` | `surface-config` — Topside's `vendor:publish` tag |

[^topside][^enums-test]

# FirstPartyExtension (`string`)

Value = the token `pie install` takes: Packagist package + version line.[^extension][^extension-test]

| Case | Value | `unsupportedOn($os_family)` |
|------|-------|-----------------------------|
| `EPOLL` | `php-io-extensions/epoll:^0.11` | `Linux only` unless `Linux` |
| `KQUEUE` | `php-io-extensions/kqueue:^0.11` | `macOS only` unless `Darwin` |
| `MULTICURL` | `php-io-extensions/multicurl:^0.11` | `not on Windows` on `Windows` |
| `APPKIT` | `php-io-extensions/appkit:^0.11` | `macOS only` unless `Darwin` |
| `GTK`, `QT`, `RAVEN` | `php-io-extensions/{name}:^0.11` | `Linux and macOS only` unless `Linux` or `Darwin` |

`extension()` = lowercase case name (each package's `php-ext.extension-name`). `core()` = `[EPOLL, KQUEUE, MULTICURL]` (`new`, `install:ext`). `toolkits()` = `[APPKIT, GTK, QT, RAVEN]` (`install:toolkit-ext`). `named($name, ?$catalog)` = case by extension name, case-insensitive, within the catalog (every case when null), or `null`. `package()` = value without the version line. `line()` = `0.11`. `developmentToken()` = `package:0.11.x-dev`. `description()` = the few words shown in the list. The OS families follow each package's `php-ext.os-families`.

# Toolkit (`string`)

Value = the `composer require` token of the jovian adapter.[^toolkit][^extension-test]

| Case | Value | `extensionsOn('Darwin')` | `extensionsOn('Linux')` |
|------|-------|--------------------------|--------------------------|
| `APPKIT` | `jovian/venusian-appkit:^0.11` | appkit | appkit (disabled: `macOS only`) |
| `GTK` | `jovian/venusian-gtk:^0.11` | gtk, appkit | gtk |
| `QT` | `jovian/venusian-qt:^0.11` | qt | qt |
| `RAVEN` | `jovian/venusian-raven:^0.11` | raven | raven |

`key()` = lowercase name. `package()`, `extension()` (the extension its adapter is written over), `description()` and `unsupportedOn()` come from that extension. GTK needs ext-appkit on macOS because its adapter refuses to start there without it ([Topside flow](/core/topside-flow.md)).

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
| `USER_AGENT` | `Venusian Installer` |
| `CACHE_BODY_FILENAME` | `venusian-installer-version-check.json` |
| `CACHE_LAST_MODIFIED_FILENAME` | `venusian-installer-last-modified` |
| `NO_UPDATE_CHECK_ENV` | `VENUSIAN_INSTALLER_NO_UPDATE_CHECK` |

`USER_AGENT` is the `User-Agent` header on Packagist requests; `exposes every InstallerPackage case value` asserts every case.[^package][^enums-test]

# InstallerReleaseChannel (`int`)

| Case | Value |
|------|-------|
| `CACHE_TTL_SECONDS` | `86400` |
| `HTTP_TIMEOUT_SECONDS` | `3` |

[^channel]

[^skeleton]: SkeletonPackage
[^topside]: TopsidePackage
[^package]: InstallerPackage
[^channel]: InstallerReleaseChannel
[^extension]: FirstPartyExtension
[^toolkit]: Toolkit
[^pie]: PiePackage
[^enums-test]: Pest coverage for the package enums
[^extension-test]: Pest coverage for FirstPartyExtension, Toolkit and PiePackage

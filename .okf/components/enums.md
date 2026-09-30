---
type: Module
title: Enums
description: String- and int-backed enums for skeleton pin, Packagist identity, and release-channel numbers.
resource: src/Enums
tags: [component, enum]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
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
  - id: enums-test
    resource: tests/Enums/EnumsTest.php
    title: Pest coverage for every case value
---

# Convention

Backed PHP enums under `src/Enums/`. Cases are FULLY UPPERCASE. No class-level constants for these maps.[^skeleton][^package][^channel][^enums-test]

# SkeletonPackage (`string`)

| Case | Value |
|------|-------|
| `VENUSIAN` | `venusian/venusian:^0.10.0` |

This is the create-project token.[^skeleton][^enums-test]

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

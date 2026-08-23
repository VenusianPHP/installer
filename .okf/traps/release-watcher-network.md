---
type: Trap
title: Release watcher network
description: PackagistReleaseWatcher must not run against a live network in tests; skip rules and cache TTL short-circuit the HTTP client.
tags: [trap, network, packagist, cache]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "agent:framework-auditor", at: "2026-08-23T17:31:14Z" }
verification_key: "agent:framework-auditor@693b56c0cf3a622988e1f7b8147370aec9ba492f"
status: stable
sources:
  - id: watcher
    resource: src/ReleaseChannel/PackagistReleaseWatcher.php
    title: shouldSkip, fetchLatestVersion, injectables
  - id: watcher-test
    resource: tests/ReleaseChannel/PackagistReleaseWatcherTest.php
    title: Pest coverage for skip and cache TTL
  - id: channel
    resource: src/Enums/InstallerReleaseChannel.php
    title: CACHE_TTL_SECONDS
---

# Trap

Default construction uses curl/streams against Packagist, `time()`, Laravel Prompts `confirm()`, and `ExecutableFinder`. Tests **must** inject `httpClient`, `clock`, `confirm`, `installedVersionResolver`, `reExec`, `terminate`, `composerFinder`, `binaryFinder`, and `cacheDirectory`.[^watcher][^watcher-test]

# Skip

No HTTP and no confirm when:[^watcher][^watcher-test]

- `VENUSIAN_INSTALLER_NO_UPDATE_CHECK` is `1` (Pest) or `true` (source only — not a named Pest case)
- the input is non-interactive (Pest)
- argv token `completion` is the first non-option argument (Pest)

# Cache (tested)

If the body cache file exists and `filemtime > clock() - 86400`, the watcher parses that cached body and does not call `httpClient`.[^channel][^watcher-test]

Default HTTP timeout is `3` seconds (`InstallerReleaseChannel::HTTP_TIMEOUT_SECONDS`). Curl vs stream fallback is not Pest-covered.

# Related

- [PackagistReleaseWatcher](/components/packagist-release-watcher.md)
- [Playbook: new application](/playbooks/new-application.md)

[^watcher]: shouldSkip, fetchLatestVersion, injectables
[^watcher-test]: Pest coverage for skip and cache TTL
[^channel]: CACHE_TTL_SECONDS

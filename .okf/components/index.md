---
type: Index
title: Components
description: Public surface of venusian/installer.
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "agent:framework-auditor", at: "2026-08-23T17:31:14Z" }
verification_key: "agent:framework-auditor@693b56c0cf3a622988e1f7b8147370aec9ba492f"
status: stable
---

# Components

Public surface of `venusian/installer`.

* [NewApplicationCommand](new-application-command.md) - `new` command; arg `name`; callout outcomes.
* [ComposerProjectCreator](composer-project-creator.md) - Injectable `composer create-project` helper.
* [ProcessRunner](process-runner.md) - Process exit-code / stdout helper.
* [PackagistReleaseWatcher](packagist-release-watcher.md) - Packagist self-update offer.
* [ResolveDirectory](resolve-directory.md) - Directory argument → path.
* [PrepareSharedBag](prepare-shared-bag.md) - Initial PocketFlow shared bag.
* [Enums](enums.md) - Skeleton, installer package, and release-channel enums.
* [Application](application.md) - Symfony Console app wrapping the watcher.

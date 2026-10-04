---
type: Index
title: Components
description: Public surface of venusian/installer.
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
verification_key: "claude-fable-5-1@6689f865c8b40863d13dc520135c0217579d3224"
status: stable
---

# Components

Public surface of `venusian/installer`.

* [NewApplicationCommand](new-application-command.md) - `new` command; arg `name`; callout outcomes.
* [InstallExtensionCommand](install-extension-command.md) - `install:ext {ext?}`; whole extension catalog.
* [ComposerProjectCreator](composer-project-creator.md) - Injectable `composer create-project` helper.
* [ProcessRunner](process-runner.md) - Process exit-code / stdout helper.
* [Host](host.md) - The machine and the PHP binary running the installer.
* [ChecklistPrompt](checklist-prompt.md) - Multi-select with disabled rows.
* [SummarizeExtensions](summarize-extensions.md) - Extension outcome as callout content.
* [PackagistReleaseWatcher](packagist-release-watcher.md) - Packagist self-update offer.
* [ResolveDirectory](resolve-directory.md) - Directory argument → path.
* [PrepareSharedBag](prepare-shared-bag.md) - Initial PocketFlow shared bag.
* [Enums](enums.md) - Skeleton, first-party extension, PIE, installer package, and release-channel enums.
* [Application](application.md) - Symfony Console app wrapping the watcher.

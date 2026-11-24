---
type: Index
title: Components
description: Public surface of venusian/installer.
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
---

# Components

Public surface of `venusian/installer`.

* [NewApplicationCommand](new-application-command.md) - `new` command; arg `name`; callout outcomes; exit codes.
* [InstallExtensionCommand and InstallToolkitExtensionCommand](install-extension-command.md) - `install:ext {ext?}` (epoll, kqueue, multicurl), `install:toolkit-ext {ext?}` (appkit, gtk, qt, raven).
* [InstallSdkCommand and ToolCommands](install-sdk-command.md) - removed in 0.11.0. (`deprecated`)
* [ComposerProjectCreator](composer-project-creator.md) - removed in 0.11.0. (`deprecated`)
* [ProcessRunner](process-runner.md) - Process exit-code / stdout helper.
* [Host](host.md) - The machine and the PHP binary running the installer.
* [ChecklistPrompt](checklist-prompt.md) - Multi-select with disabled rows.
* [SummarizeSteps](summarize-steps.md) - Optional steps' outcomes as callout content.
* [PackagistReleaseWatcher](packagist-release-watcher.md) - Packagist self-update offer.
* [ResolveDirectory](resolve-directory.md) - Directory argument → path.
* [PrepareSharedBag](prepare-shared-bag.md) - Initial PocketFlow shared bag.
* [Enums](enums.md) - Skeleton, Topside, first-party extension, toolkit, PIE, installer package, and release-channel enums.
* [Application](application.md) - Symfony Console app wrapping the watcher.

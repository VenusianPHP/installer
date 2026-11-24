---
okf_version: "0.2"
status: stable
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
verification_key: "claude-fable-5-1@6689f865c8b40863d13dc520135c0217579d3224"
---

# venusian/installer Knowledge Bundle

Package knowledge for `venusian/installer` (global Composer CLI that scaffolds Venusian apps, v0.11.0). Successor of `scrapyard-io/installer`.
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. A concept re-checked against the tree carries `verified` + `verification_key` (`<agent>@<commit>`) and `status: stable`; a concept an agent creates stays `draft` until a human verifies it.
**Placement:** Package-root `.okf/` only — not under `src/` or `tests/`.
**Scope:** This installer package only. Voyager domain rules live in `venusian/framework` OKF. Probe REPL knowledge lives in `venusian/probe` OKF.
**Assertion rule:** Behavioral claims name the Pest test that backs them; a claim without one says so in place.
**Version note:** Claims track installer **0.11.0**. Skeleton pin is `venusian/venusian:^0.11.1`; Topside `^0.11`; extensions and adapters on their 0.11 lines.
**Verified:** concepts touched on 2026-11-24 are `draft` until a human verifies them. See [log](log.md).

# Orientation

* [Package](orientation/package.md) - Composer identity, namespace, bin, commands, role vs framework/probe. (`draft`)

# Core

* [Node orchestration](core/node-orchestration.md) - PocketFlow `new` flow: Start → ComposerFinder → ProjectCreation → ExtensionsFlow → Topside flow; one PHP binary; named actions, null stops; shared bag keys. (`draft`)
* [ExtensionsFlow](core/extensions-flow.md) - Extension step: offer, php-config, PIE, select, install; notes and outcomes. (`draft`)
* [Topside flow](core/topside-flow.md) - venusian/topside and its config publish, then the toolkit SDKs: extension through PIE, then the jovian adapter. (`draft`)

# Components

* [Components](components/) - Commands, process runner, host, release watcher, actions, prompt, enums, application.
* [NewApplicationCommand](components/new-application-command.md) - `new` command; arg `name`; callout outcomes; exit codes. (`draft`)
* [InstallExtensionCommand and InstallToolkitExtensionCommand](components/install-extension-command.md) - `install:ext {ext?}` (epoll, kqueue, multicurl), `install:toolkit-ext {ext?}` (appkit, gtk, qt, raven); exit codes. (`draft`)
* [InstallSdkCommand and ToolCommands](components/install-sdk-command.md) - removed in 0.11.0; venusian/build is to return later. (`deprecated`)
* [ComposerProjectCreator](components/composer-project-creator.md) - removed in 0.11.0. (`deprecated`)
* [ProcessRunner](components/process-runner.md) - Exit-code / succeeds / trimmed stdout helper. (`stable`)
* [Host](components/host.md) - PHP binary and its build identity, `phpCommand()`, OS family, loaded extensions, home, executable lookup, download. (`draft`)
* [ChecklistPrompt](components/checklist-prompt.md) - Multi-select with disabled rows. (`draft`)
* [SummarizeSteps](components/summarize-steps.md) - Optional steps' outcomes → success callout content. (`draft`)
* [PackagistReleaseWatcher](components/packagist-release-watcher.md) - Self-update offer on `doRun`. (`stable`)
* [ResolveDirectory](components/resolve-directory.md) - `.` / absolute / relative path resolution. (`stable`)
* [PrepareSharedBag](components/prepare-shared-bag.md) - Initial `$shared` keys for the flow. (`stable`)
* [Enums](components/enums.md) - `SkeletonPackage`, `TopsidePackage`, `FirstPartyExtension`, `Toolkit`, `PiePackage`, `InstallerPackage`, `InstallerReleaseChannel`. (`draft`)
* [Application](components/application.md) - Symfony Console app; release watcher hook; `bin/venusian`. (`stable`)

# Playbooks

* [Playbooks](playbooks/) - How to run `venusian new` and develop this checkout.
* [New application](playbooks/new-application.md) - Run `venusian new`; the extension, Topside and toolkit SDK steps; disable the update check. (`draft`)
* [Local development](playbooks/local-development.md) - Pest, `php bin/venusian`, path-repo notes. (`stable`)

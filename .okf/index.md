---
okf_version: "0.2"
status: stable
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
---

# venusian/installer Knowledge Bundle

Package knowledge for `venusian/installer` (global Composer CLI that scaffolds Venusian apps, v0.10.0). Successor of `scrapyard-io/installer`.
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. A concept re-checked against the tree carries `verified` + `verification_key` (`<agent>@<commit>`) and `status: stable`; a concept an agent creates stays `draft` until a human verifies it.
**Placement:** Package-root `.okf/` only — not under `src/` or `tests/`.
**Scope:** This installer package only. Voyager domain rules live in `venusian/framework` OKF. Probe REPL knowledge lives in `venusian/probe` OKF.
**Assertion rule:** Behavioral claims name the Pest test that backs them; a claim without one says so in place.
**Version note:** Claims track installer **0.10.0**. Skeleton pin is `venusian/venusian:^0.10.0`.
**Verified:** against the tree at `1ce46f6`. See [log](log.md).

# Orientation

* [Package](orientation/package.md) - Composer identity, namespace, bin, role vs framework/probe. (`stable`)

# Core

* [Node orchestration](core/node-orchestration.md) - PocketFlow `new` flow: Start → ComposerFinder → ProjectCreation; shared bag keys. (`stable`)

# Components

* [Components](components/) - Command, creator, process runner, release watcher, actions, enums, application.
* [NewApplicationCommand](components/new-application-command.md) - Computer-style `new` command; arg `name`; callout outcomes. (`stable`)
* [ComposerProjectCreator](components/composer-project-creator.md) - `composer create-project` argv + injectable finder/factory. (`stable`)
* [ProcessRunner](components/process-runner.md) - Exit-code / succeeds / trimmed stdout helper. (`stable`)
* [PackagistReleaseWatcher](components/packagist-release-watcher.md) - Self-update offer on `doRun`. (`stable`)
* [ResolveDirectory](components/resolve-directory.md) - `.` / absolute / relative path resolution. (`stable`)
* [PrepareSharedBag](components/prepare-shared-bag.md) - Initial `$shared` keys for the flow. (`stable`)
* [Enums](components/enums.md) - `SkeletonPackage`, `InstallerPackage`, `InstallerReleaseChannel`. (`stable`)
* [Application](components/application.md) - Symfony Console app; release watcher hook; `bin/venusian`. (`stable`)

# Playbooks

* [Playbooks](playbooks/) - How to run `venusian new` and develop this checkout.
* [New application](playbooks/new-application.md) - Run `venusian new`; disable the update check. (`stable`)
* [Local development](playbooks/local-development.md) - Pest, `php bin/venusian`, path-repo notes. (`stable`)

---
okf_version: "0.2"
---

# venusian/installer Knowledge Bundle

Package knowledge for `venusian/installer` (global Composer CLI that scaffolds Venusian apps, v0.8.0). Successor of `scrapyard-io/installer`.
Read this index first; open only the concepts needed for the task.

**Trust rule:** Prefer `status: stable`. Treat `deprecated` as historical only. Concepts below are `draft` until a human verifies them.
**Placement:** Package-root `.okf/` only — not under `src/` or `tests/`.
**Scope:** This installer package only. Voyager domain rules live in `venusian/framework` OKF. Probe REPL knowledge lives in `venusian/probe` OKF.
**Assertion rule:** Behavioral claims in [core](core/node-orchestration.md) and [components](components/) map to named Pest tests. Unbacked runtime claims live in [traps](traps/stale-branding.md) or [known gaps](known-gaps.md).
**Version note:** Claims track installer **0.8.0**. Skeleton pin is `venusian/venusian:^0.8.2` (not `^0.8.0`).

# Orientation

* [Package (0.8)](orientation/package.md) - Composer identity, namespace, bin, role vs framework/probe.

# Core

* [Node orchestration](core/node-orchestration.md) - PocketFlow `new` flow: Start → ComposerFinder → ProjectCreation; shared bag keys.

# Components

* [Components](components/) - Command, creator, process runner, release watcher, actions, enums, application.
* [NewApplicationCommand](components/new-application-command.md) - Computer-style `new` command; arg `name`; callout outcomes. (`draft`)
* [ComposerProjectCreator](components/composer-project-creator.md) - `composer create-project` argv + injectable finder/factory. (`draft`)
* [ProcessRunner](components/process-runner.md) - Exit-code / succeeds / trimmed stdout helper. (`draft`)
* [PackagistReleaseWatcher](components/packagist-release-watcher.md) - Self-update offer on `doRun`. (`draft`)
* [ResolveDirectory](components/resolve-directory.md) - `.` / absolute / relative path resolution. (`draft`)
* [PrepareSharedBag](components/prepare-shared-bag.md) - Initial `$shared` keys for the flow. (`draft`)
* [Enums](components/enums.md) - `SkeletonPackage`, `InstallerPackage`, `InstallerReleaseChannel`. (`draft`)
* [Application](components/application.md) - Symfony Console app; release watcher hook. (`draft`)

# Playbooks

* [Playbooks](playbooks/) - How to run `venusian new` and develop this checkout.
* [New application](playbooks/new-application.md) - Run `venusian new`; disable the update check. (`draft`)
* [Local development](playbooks/local-development.md) - Pest, `php bin/venusian`, path-repo notes. (`draft`)

# Traps

* [Stale branding](traps/stale-branding.md) - `ScrapyardIO` / `scrapyard-io` leftovers still in source. (`draft`)
* [Release watcher network](traps/release-watcher-network.md) - Skip rules, cache TTL, injected HTTP. (`draft`)

# Gaps

* [Known gaps](known-gaps.md) - AGENTS.md drift, unused injections, empty `actions` bag, missing canvas.

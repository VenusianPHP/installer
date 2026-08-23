---
type: Known Issues
title: Known gaps
description: Documented drift and untested behavior in venusian/installer 0.8.0 — not product intent.
tags: [gaps, drift, reconstitution]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: agents
    resource: AGENTS.md
    title: Agent guidelines
  - id: skeleton
    resource: src/Enums/SkeletonPackage.php
    title: Actual skeleton pin
  - id: command
    resource: src/Console/Commands/NewApplicationCommand.php
    title: Unused injections and exit code
  - id: bag
    resource: src/Actions/PrepareSharedBag.php
    title: Empty actions array
  - id: watcher
    resource: src/ReleaseChannel/PackagistReleaseWatcher.php
    title: Stale branding strings
  - id: readme
    resource: README.md
    title: README title leftover
---

# Do not paper over

These are true of the tree. Core/component concepts state tested behavior; this file holds the rest.

# AGENTS.md skeleton pin

`SkeletonPackage::VENUSIAN` is `venusian/venusian:^0.8.2`.[^skeleton] AGENTS.md said `^0.8.0` until 2026-08-23; the guideline now matches the enum. Do not "fix" the enum back to `^0.8.0`.

# Plan → actions log is not wired

AGENTS.md described PocketFlow as plan → `$shared['actions']` → execute. `PrepareSharedBag` still seeds `'actions' => []`, and no node appends to it.[^bag] `new` runs a linear Start → ComposerFinder → ProjectCreation flow. See [node orchestration](/core/node-orchestration.md).

# Stale branding

[Stale branding](/traps/stale-branding.md) — `ScrapyardIO Installer` user-agent and `scrapyard-io` PATH error. Tests pin the literals.[^watcher]

# NewApplicationCommand leftovers

- Constructor `ComposerProjectCreator` and `Filesystem` are unused; `execute()` delegates creation to `ProjectCreationNode`.[^command]
- `execute()` always `return 0` after callouts (success, failure, incomplete, malformed). Not Pest-covered.
- Callout copy is source-only; Pest covers the nodes, not the Symfony command wrapper.

# README title

`README.md` still opens with `# Laravel Installer`. Packagist badges already point at `venusian/installer`.[^readme]

# Missing canvas

AGENTS.md points at `docs/canvases/installer-logic-path.canvas.tsx` (`export-ignore`). That file is not in this checkout.

# ComposerFinderNode is not injectable

`exec()` constructs `new ExecutableFinder()`. Pest drives `post()` with a synthetic `$exec_res` instead of finding a real `composer` binary.

# Related

- [Node orchestration](/core/node-orchestration.md)
- [Stale branding](/traps/stale-branding.md)
- [Package](/orientation/package.md)

[^agents]: Agent guidelines
[^skeleton]: Actual skeleton pin
[^command]: Unused injections and exit code
[^bag]: Empty actions array
[^watcher]: Stale branding strings
[^readme]: README title leftover

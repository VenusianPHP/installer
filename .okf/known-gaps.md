---
type: Known Issues
title: Known gaps
description: Documented drift and untested behavior in venusian/installer 0.8.0 — not product intent.
tags: [gaps, drift, reconstitution]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "agent:framework-auditor", at: "2026-08-23T17:31:14Z" }
verification_key: "agent:framework-auditor@693b56c0cf3a622988e1f7b8147370aec9ba492f"
status: stable
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
  - id: gitattributes
    resource: .gitattributes
    title: export-ignore list
---

# Do not paper over

These are true of the tree. Core/component concepts state tested behavior; this file holds the rest.

# AGENTS.md skeleton pin

`SkeletonPackage::VENUSIAN` is `venusian/venusian:^0.8.2`.[^skeleton] `AGENTS.md` at `693b56c` already matches that pin. Do not "fix" the enum back to `^0.8.0`.

# Plan → actions log is not wired

AGENTS.md prefers orchestrated setup (plan → stages) as features return. `PrepareSharedBag` still seeds `'actions' => []`, and no node appends to it.[^bag] `new` runs a linear Start → ComposerFinder → ProjectCreation flow. See [node orchestration](/core/node-orchestration.md).

# Stale branding

[Stale branding](/traps/stale-branding.md) — `ScrapyardIO Installer` user-agent (Pest-pinned) and `scrapyard-io` PATH error / `requireConstraint` docblock (source-only).[^watcher]

# NewApplicationCommand leftovers

- Constructor `ComposerProjectCreator` and `Filesystem` are unused; `execute()` delegates creation to `ProjectCreationNode`.[^command]
- `execute()` always `return 0` after callouts (success, failure, incomplete, malformed). Not Pest-covered.
- Callout copy is source-only; Pest covers the nodes, not the Symfony command wrapper.
- Directory resolution uses `trim($name, '/\\')` (both ends). The shared bag `name` uses `rtrim($name, '/\\')` (trailing only).

# README title and missing mermaid

`README.md` still opens with `# Laravel Installer`. Packagist badges already point at `venusian/installer`.[^readme]

AGENTS.md says the README embeds a mermaid overview. `README.md` has no mermaid.

# Missing canvas and CHANGELOG

AGENTS.md points at `docs/canvases/installer-logic-path.canvas.tsx` (`export-ignore` via `.gitattributes`). There is no `docs/` directory. `.gitattributes` does **not** list `docs/` or that canvas path.[^gitattributes]

`.gitattributes` lists `/CHANGELOG.md export-ignore`, but `CHANGELOG.md` is not in the tree.

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
[^gitattributes]: export-ignore list

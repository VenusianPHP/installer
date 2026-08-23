---
type: Orientation
title: Package (0.8)
description: venusian/installer 0.8.0 — global Composer CLI that scaffolds Venusian PHP applications.
resource: composer.json
tags: [orientation, installer, venusian, 0.8]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: composer
    resource: composer.json
    title: Package name, version, require, bin, autoload
  - id: bin
    resource: bin/venusian
    title: CLI entrypoint
  - id: skeleton
    resource: src/Enums/SkeletonPackage.php
    title: create-project skeleton pin
  - id: enums-test
    resource: tests/Enums/EnumsTest.php
    title: Pest coverage for skeleton and installer enums
---

# What it is

Composer package `venusian/installer` at **0.8.0** — a globally installable Symfony Console CLI (not a full Symfony HTTP app) whose bin is `bin/venusian`. Successor of `scrapyard-io/installer`.[^composer][^bin]

| Field | Value |
|-------|-------|
| Name | `venusian/installer`[^composer] |
| Version | `0.8.0`[^composer] |
| PHP | `^8.4\|^8.5\|^8.6`[^composer] |
| Namespace | `Venusian\Installer\` → `src/`[^composer] |
| Bin | `bin/venusian`[^composer][^bin] |
| Role | Scaffold a Venusian app via `composer create-project` |
| Skeleton | `venusian/venusian:^0.8.2` (`SkeletonPackage::VENUSIAN`)[^skeleton][^enums-test] |

`.okf/`, `AGENTS.md`, and `CHANGELOG.md` are intended `export-ignore` from Composer dist (see package `.gitattributes`).

# Requires (0.8)

| Package | Constraint |
|---------|------------|
| `laravel/prompts` | `^0.3` |
| `projectsaturnstudios/pocketflow-php` | `^0.2` |
| `symfony/console` | `^7.0\|^8.0` |
| `symfony/filesystem` | `^7.0\|^8.0` |
| `symfony/process` | `^7.0\|^8.0` |

Does **not** require `venusian/framework` or `venusian/probe`.[^composer]

# What it is not

- Not a Voyager domain under `src/Voyager/*`. Framework architecture belongs in `venusian/framework` OKF.
- Not the PsySH REPL. That is `venusian/probe` (`computer probe`).
- Not the application skeleton. `new` installs `venusian/venusian:^0.8.2`.[^skeleton][^enums-test]

# Key files

| Path | Role |
|------|------|
| `bin/venusian` | Console entry (`Application` 0.8.0 + `NewApplicationCommand`) |
| `src/Console/Application.php` | `doRun` → Packagist self-update offer |
| `src/Console/Commands/NewApplicationCommand.php` | `new` flow host |
| `src/Enums/SkeletonPackage.php` | Skeleton Composer constraint |
| `tests/` | Pest v4 specs mapped from this bundle |

# Related

| Topic | Concept |
|-------|---------|
| Flow | [Node orchestration](/core/node-orchestration.md) |
| Command | [NewApplicationCommand](/components/new-application-command.md) |
| Run it | [New application](/playbooks/new-application.md) |
| Stale copy | [Stale branding](/traps/stale-branding.md) |

[^composer]: Package name, version, require, bin, autoload
[^bin]: CLI entrypoint
[^skeleton]: create-project skeleton pin
[^enums-test]: Pest coverage for skeleton and installer enums

---
type: Orientation
title: Package
description: venusian/installer 0.10.0 — global Composer CLI that scaffolds Venusian PHP applications.
resource: composer.json
tags: [orientation, installer, venusian, 0.10]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
status: stable
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

Composer package `venusian/installer` at **0.10.0** — a globally installable Symfony Console CLI (not a full Symfony HTTP app) whose bin is `bin/venusian`. Successor of `scrapyard-io/installer`.[^composer][^bin]

| Field | Value |
|-------|-------|
| Name | `venusian/installer`[^composer] |
| Version | `0.10.0`[^composer] |
| PHP | `^8.4\|^8.5\|^8.6`[^composer] |
| Namespace | `Venusian\Installer\` → `src/`[^composer] |
| Bin | `bin/venusian`[^composer][^bin] |
| Role | Scaffold a Venusian app via `composer create-project` |
| Skeleton | `venusian/venusian:^0.10.0` (`SkeletonPackage::VENUSIAN`)[^skeleton][^enums-test] |

`.gitattributes` marks `/tests`, `/phpunit.xml`, `/.github`, `/.okf`, `/AGENTS.md`, `/CHANGELOG.md` `export-ignore`. No `CHANGELOG.md` in the tree.

# Requires

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
- Not the application skeleton. `new` installs `venusian/venusian:^0.10.0`.[^skeleton][^enums-test]

# Key files

| Path | Role |
|------|------|
| `bin/venusian` | Console entry (`Application` 0.10.0 + `NewApplicationCommand`); version equals `composer.json` (`BinaryTest`) |
| `src/Console/Application.php` | `doRun` → Packagist self-update offer |
| `src/Console/Commands/NewApplicationCommand.php` | `new` flow host |
| `src/Enums/SkeletonPackage.php` | Skeleton Composer constraint |
| `tests/` | Pest v4 specs (`pestphp/pest` `^4` in `require-dev`) |

# Tree (measured at 1ce46f6)

| Item | Measured |
|------|----------|
| `src/**/*.php` | 14 files |
| `src/Enums/` | 3 backed enums, all cases FULLY UPPERCASE; no `const` in `src/` |
| `tests/**/*Test.php` | 9 Pest spec files, 36 `it()` cases |
| `tests/Pest.php` | Pest v4 bootstrap (`Mockery::close()` in `afterEach`) |
| `bin/` | `bin/venusian` only |
| Root docs | `README.md` (install, usage, mermaid flow, update opt-out), `SECURITY.md`, `AGENTS.md`, `CLAUDE.md` → `AGENTS.md` |
| CI | `.github/workflows/tests.yml` — PHP `8.4` and `8.5`, `vendor/bin/pest`, `actions/checkout@v7` |
| `composer.lock` | gitignored (`.gitignore`); CI runs `composer update` |

# Related

| Topic | Concept |
|-------|---------|
| Flow | [Node orchestration](/core/node-orchestration.md) |
| Command | [NewApplicationCommand](/components/new-application-command.md) |
| Run it | [New application](/playbooks/new-application.md) |

[^composer]: Package name, version, require, bin, autoload
[^bin]: CLI entrypoint
[^skeleton]: create-project skeleton pin
[^enums-test]: Pest coverage for skeleton and installer enums

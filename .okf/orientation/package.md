---
type: Orientation
title: Package
description: venusian/installer 0.11.0 — global Composer CLI that scaffolds Venusian PHP applications, then offers the PHP extensions, Topside and the toolkit SDKs.
resource: composer.json
tags: [orientation, installer, venusian, 0.11]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
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

Composer package `venusian/installer` at **0.11.0** — a globally installable Symfony Console CLI whose bin is `bin/venusian`. Successor of `scrapyard-io/installer`.[^composer][^bin]

| Field | Value |
|-------|-------|
| Name | `venusian/installer`[^composer] |
| Version | `0.11.0`[^composer] |
| PHP | `^8.4\|^8.5\|^8.6`[^composer] |
| Namespace | `Venusian\Installer\` → `src/`[^composer] |
| Bin | `bin/venusian`[^composer][^bin] |
| Commands | `new`, `install:ext`, `install:toolkit-ext` |
| Role | Scaffold a Venusian app via `composer create-project`, offer epoll/kqueue/multicurl through PIE, then venusian/topside and the toolkit SDKs — every process under the PHP binary running the installer |
| Skeleton | `venusian/venusian:^0.11.1` (`SkeletonPackage::VENUSIAN`)[^skeleton][^enums-test] |

`.gitattributes` marks `/tests`, `/phpunit.xml`, `/.github`, `/.okf`, `/AGENTS.md`, `/CHANGELOG.md` `export-ignore`. No `CHANGELOG.md` in the tree.

venusian/build (`install:sdk`, the `venusian-tool` command discovery) is decoupled from 0.11.0; see the deprecated [InstallSdkCommand](/components/install-sdk-command.md).

# Requires

| Package | Constraint |
|---------|------------|
| `laravel/prompts` | `^0.3.19` |
| `projectsaturnstudios/pocketflow-php` | `^0.2` |
| `symfony/console` | `^7.4\|^8.0` |
| `symfony/filesystem` | `^7.0\|^8.0` |
| `symfony/process` | `^7.0\|^8.0` |

Does **not** require `venusian/framework`, `venusian/topside` or any adapter: those go into the application it creates.[^composer]

# What it is not

- Not a Voyager domain under `src/Voyager/*`. Framework architecture belongs in `venusian/framework` OKF.
- Not the PsySH REPL. That is `venusian/probe` (`computer probe`).
- Not the application skeleton. `new` installs `venusian/venusian:^0.11.1`.[^skeleton][^enums-test]

# Key files

| Path | Role |
|------|------|
| `bin/venusian` | Console entry (`Application` 0.11.0 + three commands); version equals `composer.json` (`BinaryTest`) |
| `src/Console/Application.php` | `doRun` → Packagist self-update offer |
| `src/Console/Commands/` | `new`, `install:ext`, `install:toolkit-ext` |
| `src/Workflows/NewApplication/` | `NewApplicationFlow` and its nodes |
| `src/Workflows/Extensions/` | `ExtensionsFlow` and its six nodes |
| `src/Workflows/Topside/` | Topside and toolkit SDK nodes |
| `src/Enums/` | Skeleton, Topside, extensions, toolkits, PIE, installer package, release channel |
| `src/Host.php` | The machine and the running PHP binary |
| `src/Prompts/` | `ChecklistPrompt`: multi-select with disabled rows |
| `tests/` | Pest v4 specs (`pestphp/pest` `^4` in `require-dev`) |

# Tree (measured 2026-11-24)

| Item | Measured |
|------|----------|
| `src/**/*.php` | 35 files |
| `src/Enums/` | 7 backed enums, all cases FULLY UPPERCASE; no `const` in `src/` |
| `tests/**/*Test.php` | 15 Pest spec files, 109 `it()` cases; `tests/Fakes/` holds 2 test doubles |
| `bin/` | `bin/venusian` only |
| Root docs | `README.md`, `SECURITY.md`, `AGENTS.md`, `CLAUDE.md` → `AGENTS.md` |
| CI | `.github/workflows/tests.yml` — PHP `8.4` and `8.5`, `vendor/bin/pest`, `actions/checkout@v7` |
| `composer.lock` | gitignored; CI runs `composer update` |

# Related

| Topic | Concept |
|-------|---------|
| Flow | [Node orchestration](/core/node-orchestration.md) |
| Extension step | [ExtensionsFlow](/core/extensions-flow.md) |
| Topside and toolkits | [Topside flow](/core/topside-flow.md) |
| Command | [NewApplicationCommand](/components/new-application-command.md) |
| Run it | [New application](/playbooks/new-application.md) |

[^composer]: Package name, version, require, bin, autoload
[^bin]: CLI entrypoint
[^skeleton]: create-project skeleton pin
[^enums-test]: Pest coverage for skeleton and installer enums

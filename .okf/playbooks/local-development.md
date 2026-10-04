---
type: Playbook
title: Local development
description: Install and test this installer checkout with Pest v4. No venusian/framework clone is required.
tags: [playbook, pest, composer, development]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
verification_key: "claude-fable-5-1@6689f865c8b40863d13dc520135c0217579d3224"
status: stable
sources:
  - id: composer
    resource: composer.json
    title: Package require / autoload-dev / bin
  - id: bin
    resource: bin/venusian
    title: Local CLI entry
  - id: pest
    resource: tests/Pest.php
    title: Pest v4 bootstrap
---

# Prerequisites

- PHP `8.4` or `8.5` (package also allows `8.6`).[^composer]
- Composer 2.
- This tree is often on a mounted volume. Run `composer` / `pest` / `php bin/venusian` on a machine that can execute PHP — not via host utilities against the mount.

# Install

From the installer checkout:

```bash
composer install
php bin/venusian
```

`php bin/venusian` lists commands; `new` is registered.[^bin]

This package does **not** path-repo `venusian/framework`. Tests are package-level and inject process/HTTP/clock collaborators.

# Tests

```bash
vendor/bin/pest
```

`composer.json` `require-dev` is `pestphp/pest` `^4` and `mockery/mockery` `^1.6`. Specs live under `tests/` (`Venusian\Installer\Tests\`). They do not hit the network, exec `composer` or exec `pie`; `BinaryTest` runs `bin/venusian` with the update check off, and `NewApplicationCommandTest` runs `new` against a stand-in `composer` script on a private `PATH`. `tests/Fakes/` holds `FakeHost` and `FakeProcessRunner`; prompts are driven with `Prompt::fake()`.[^pest]

Named Pest files (81 `it()` cases; 91 tests with the `FirstPartyExtensionTest` dataset):

| File | `it()` count |
|------|----------------|
| `tests/Enums/EnumsTest.php` | 3 |
| `tests/Enums/FirstPartyExtensionTest.php` | 5 |
| `tests/Actions/PrepareSharedBagTest.php` | 3 |
| `tests/Actions/ResolveDirectoryTest.php` | 3 |
| `tests/Actions/SummarizeExtensionsTest.php` | 3 |
| `tests/Workflows/NewApplicationFlowNodesTest.php` | 6 |
| `tests/Workflows/ExtensionsFlowTest.php` | 21 |
| `tests/Prompts/ChecklistPromptTest.php` | 7 |
| `tests/HostTest.php` | 6 |
| `tests/ComposerProjectCreatorTest.php` | 5 |
| `tests/ProcessRunnerTest.php` | 7 |
| `tests/ReleaseChannel/PackagistReleaseWatcherTest.php` | 6 |
| `tests/Console/ApplicationTest.php` | 1 |
| `tests/Console/NewApplicationCommandTest.php` | 3 |
| `tests/BinaryTest.php` | 2 |

CI (`.github/workflows/tests.yml`) runs `vendor/bin/pest` on PHP `8.4` and `8.5` (`actions/checkout@v7`). `composer.lock` is gitignored; the workflow uses `composer update`.

To try `venusian new` against a local skeleton checkout, add a Composer path repository for `venusian/venusian` on the **machine that runs Composer**, then run `php bin/venusian new …` there.

# Related

- [Package](/orientation/package.md)

[^composer]: Package require / autoload-dev / bin
[^bin]: Local CLI entry
[^pest]: Pest v4 bootstrap

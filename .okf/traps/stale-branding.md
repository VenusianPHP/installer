---
type: Trap
title: Stale branding
description: ScrapyardIO / scrapyard-io literals still live in the 0.8 installer; they are leftovers, not intended product copy.
tags: [trap, branding, scrapyard]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: package-enum
    resource: src/Enums/InstallerPackage.php
    title: USER_AGENT leftover
  - id: watcher
    resource: src/ReleaseChannel/PackagistReleaseWatcher.php
    title: scrapyard-io PATH error and requireConstraint comment
  - id: enums-test
    resource: tests/Enums/EnumsTest.php
    title: Pest asserts the leftover USER_AGENT literal
---

# Trap

The package identity is `venusian/installer` / bin `venusian`. Several strings still say ScrapyardIO.[^package-enum][^watcher]

Do **not** document these as the intended brand. Tests pin the current literals so a rename is a failing test, not a silent docs drift.[^enums-test]

# Current leftovers

| Location | Literal |
|----------|---------|
| `InstallerPackage::USER_AGENT` | `ScrapyardIO Installer` |
| Watcher PATH miss after a successful `global require` | `Updated, but could not find scrapyard-io on PATH. Re-run your command manually.` |
| `requireConstraint` docblock | `scrapyard-io/installer:^0.7.1` example |

`requireConstraint()` itself concatenates `InstallerPackage::COMPOSER->value` (`venusian/installer`) plus `:^` plus the latest version — the stale name is comment-only.

# Related

- [Enums](/components/enums.md)
- [PackagistReleaseWatcher](/components/packagist-release-watcher.md)
- [Known gaps](/known-gaps.md)

[^package-enum]: USER_AGENT leftover
[^watcher]: scrapyard-io PATH error and requireConstraint comment
[^enums-test]: Pest asserts the leftover USER_AGENT literal

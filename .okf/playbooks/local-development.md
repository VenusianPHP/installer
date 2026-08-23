---
type: Playbook
title: Local development
description: Install and test this installer checkout with Pest v4. No venusian/framework clone is required.
tags: [playbook, pest, composer, development]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
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

Pest v4 specs live under `tests/` (`Venusian\Installer\Tests\`). They must not hit the network or exec a real `composer`.[^pest]

To try `venusian new` against a local skeleton checkout, add a Composer path repository for `venusian/venusian` on the **machine that runs Composer**, then run `php bin/venusian new …` there.

# Related

- [Package](/orientation/package.md)
- [Known gaps](/known-gaps.md)

[^composer]: Package require / autoload-dev / bin
[^bin]: Local CLI entry
[^pest]: Pest v4 bootstrap

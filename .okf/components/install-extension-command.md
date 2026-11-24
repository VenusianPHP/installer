---
type: Module
title: InstallExtensionCommand and InstallToolkitExtensionCommand
description: "`install:ext {ext?}` (epoll, kqueue, multicurl) and `install:toolkit-ext {ext?}` (appkit, gtk, qt, raven) — install first-party PHP extensions through PIE, from a list or by name."
resource: src/Console/Commands/InstallExtensionCommand.php
tags: [component, command, extensions, pie]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
sources:
  - id: command
    resource: src/Console/Commands/InstallExtensionCommand.php
    title: InstallExtensionCommand
  - id: toolkit-command
    resource: src/Console/Commands/InstallToolkitExtensionCommand.php
    title: InstallToolkitExtensionCommand
  - id: command-test
    resource: tests/Console/InstallExtensionCommandTest.php
    title: Pest coverage for execute()
  - id: binary-test
    resource: tests/BinaryTest.php
    title: Pest coverage for registration in bin/venusian
---

# Surface

`#[AsCommand(name: 'install:ext')]` and `#[AsCommand(name: 'install:toolkit-ext')]`, both registered in `bin/venusian`. `InstallToolkitExtensionCommand` extends `InstallExtensionCommand` and overrides only the static `catalog()`.[^command][^toolkit-command][^binary-test]

| Piece | Value |
|-------|-------|
| Argument | `ext` — optional — extension name; omit for the list |
| Catalog | `install:ext`: `FirstPartyExtension::core()` (epoll, kqueue, multicurl). `install:toolkit-ext`: `FirstPartyExtension::toolkits()` (appkit — macOS only — gtk, qt, raven) |
| Flow | `new ExtensionsFlow(catalog: static::catalog(), ask: false, preselect: false)`; the constructor takes a flow for tests |
| Bag | `interactive`, `extension_only` (the argument), `output_callback` |

Runs [ExtensionsFlow](/core/extensions-flow.md) alone: no composer, no application directory.

# Outcomes (tested)

| Bag after the flow | Shown | Exit |
|---|---|---|
| `extension_results`, all `installed` or `installed (0.11.x-dev)` | callout `Extensions Installed`, key/value list | 0 |
| `extension_results`, any other outcome | callout `Extensions Not All Installed` (warning) | 1 |
| note starts `Nothing to install`, or `None selected.` | `info` line | 0 |
| any other note | `error` line | 1 |

All rows, both catalog lists with their disabled rows, a name outside the command's catalog (`Unknown extension [qt]. Choose one of: epoll, kqueue, multicurl.`), named installs (interactive and not), and the no-download rule: `InstallExtensionCommandTest`.[^command-test]

Neither command installs an adapter: that is the toolkit SDK step of `new` ([Topside flow](/core/topside-flow.md)).

[^command]: InstallExtensionCommand
[^toolkit-command]: InstallToolkitExtensionCommand
[^command-test]: Pest coverage for execute()
[^binary-test]: Pest coverage for registration in bin/venusian

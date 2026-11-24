---
type: Architecture
title: Topside flow
description: The steps of venusian new after the PHP extensions — offer and install venusian/topside and publish its config, then offer the toolkit SDKs (extension through PIE, then the jovian/venusian- adapter).
resource: src/Workflows/Topside/
tags: [core, pocketflow, flow, topside, toolkits]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
sources:
  - id: nodes
    resource: src/Workflows/Topside/
    title: The five nodes
  - id: toolkit
    resource: src/Enums/Toolkit.php
    title: Toolkit enum
  - id: topside
    resource: src/Enums/TopsidePackage.php
    title: TopsidePackage enum
  - id: command-test
    resource: tests/Console/NewApplicationCommandTest.php
    title: Pest coverage for every path through the step
  - id: publisher
    resource: ../topside/src/Surface/Core/Providers/TopsideServiceProvider.php
    title: TopsideServiceProvider publishes config/toolkits.php and config/main.presentation.php under surface-config
  - id: gtk-adapter
    resource: ../../jovian/venusian-gtk/src/GtkAdapterDriver.php
    title: The GTK adapter refuses to start on macOS without ext-appkit
---

# What it is

Five nodes of [NewApplicationFlow](node-orchestration.md), after `ExtensionsFlow` returns `offer-topside`.[^nodes]

```
TopsideOfferNode ── not interactive / declined: stop
        |  "install-topside"
        v
TopsideInstallNode ── composer require failed: stop
        |  "offer-toolkits"
        v
ToolkitOfferNode ── not interactive / declined: stop
        |  "select-toolkits"
        v
ToolkitSelectNode ── none selected: stop (a valid answer)
        |  "install-toolkits"
        v
ToolkitInstallNode
```

# Nodes

| Node | Does | Writes |
|---|---|---|
| `TopsideOfferNode` | `confirm('Install venusian/topside?')` | `topside_note` on a stop |
| `TopsideInstallNode` | `[...composer, 'require', 'venusian/topside:^0.11', '--no-interaction']` in the app directory, then `[php, 'computer', 'vendor:publish', '--tag=surface-config', '--no-interaction']` there | `topside_result`: `installed, config published` or `installed, but publishing its config failed (exit code N)`; `topside_note` when the require fails |
| `ToolkitOfferNode` | `confirm('Install toolkit SDKs?')` | `toolkits_note` on a stop |
| `ToolkitSelectNode` | [ChecklistPrompt](/components/checklist-prompt.md) of the four `Toolkit` cases, nothing selected; a row the OS cannot have is disabled with its reason (appkit: `macOS only`; gtk, qt, raven: `Linux and macOS only`) | `toolkits_selected`, or `toolkits_note = 'None selected.'` |
| `ToolkitInstallNode` | Runs `ExtensionsFlow(catalog: the selection's extensions, ask: false, pick: false)` on a bag of its own; then per toolkit, `[php, '--ri', ext]` for each extension it needs; all load → `[...composer, 'require', 'jovian/venusian-{key}:^0.11', '--no-interaction']` | `toolkit_results` (key → outcome) |

The publish was run for real against `venusian/venusian` 0.11.1 + `venusian/topside` 0.11.3 on 2026-11-24: it copies `config/toolkits.php` and `config/main.presentation.php`.[^publisher]

# Rules

* **Extensions first, adapter second.** Each adapter's composer.json requires its `ext-*`, so Composer refuses it until the extension loads. An adapter is required only once `php --ri` answers for every extension it needs.
* **GTK on macOS** needs ext-appkit too (`Toolkit::extensionsOn('Darwin')`): its adapter throws `GTK on macOS requires ext-appkit` otherwise.[^gtk-adapter]
* **Already loaded** extensions are left alone: the extension flow marks them `installed` and selects only the rest.
* **One require per toolkit**, so one adapter Composer cannot resolve does not stop the others.
* **Own bag for the extensions**: the toolkit step's extension run never overwrites the `extension_results` the summary shows for epoll/kqueue/multicurl.

# Outcomes (`toolkit_results`)

`installed` · `not installed: ext-{name} ({extension outcome or the extension flow's note})` · `not installed: composer require jovian/venusian-{key} failed (exit code N)`.

Every path above has an `it()` in `NewApplicationCommandTest`.[^command-test]

# Packagist, 2026-11-24

`venusian/topside` 0.11.3 and `jovian/venusian-appkit|gtk|qt` 0.11.2 are published. `jovian/venusian-raven` is not: its require fails until it is, and the toolkit's outcome says so.

# Related

- [Node orchestration](node-orchestration.md)
- [ExtensionsFlow](extensions-flow.md)
- [Enums](/components/enums.md) — `Toolkit`, `TopsidePackage`

[^nodes]: The five nodes
[^toolkit]: Toolkit enum
[^topside]: TopsidePackage enum
[^command-test]: Pest coverage for every path through the step
[^publisher]: TopsideServiceProvider publishes config/toolkits.php and config/main.presentation.php under surface-config
[^gtk-adapter]: The GTK adapter refuses to start on macOS without ext-appkit

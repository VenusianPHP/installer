---
type: Architecture
title: ExtensionsFlow
description: The extension interview — offers a catalog of first-party extensions, installs the chosen ones through PIE run by the PHP binary running the installer. Used by new and by install:ext.
resource: src/Workflows/Extensions/
tags: [core, pocketflow, flow, extensions, pie]
generated: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
status: draft
sources:
  - id: flow
    resource: src/Workflows/Extensions/ExtensionsFlow.php
    title: ExtensionsFlow wiring
  - id: nodes
    resource: src/Workflows/Extensions/
    title: The six nodes
  - id: flow-test
    resource: tests/Workflows/ExtensionsFlowTest.php
    title: Pest coverage for every path through the flow
  - id: command-test
    resource: tests/Console/InstallExtensionCommandTest.php
    title: Pest coverage for install:ext over the whole catalog
  - id: pie
    resource: https://github.com/php/pie
    title: PIE, the PHP Installer for Extensions
---

# What it is

A `Flow` of six nodes. Runs as one node of `new`, after create-project succeeds ([node orchestration](node-orchestration.md)). Standalone too: `(new ExtensionsFlow)->run($shared)` needs only `interactive` and `output_callback` in the bag.[^flow]

# Two callers

| | `new` | `install:ext {ext?}` |
|---|---|---|
| Catalog | `FirstPartyExtension::offeredByNew()` (epoll, kqueue, pcurl) | every case |
| Asks first (`ask`) | yes: `Install first-party PHP extensions?` | no: running the command is the yes |
| List starts (`preselect`) | installable rows selected | nothing selected |
| `extension_only` | never set | the `ext` argument: that one is the selection, no list |

Constructor: `ExtensionsFlow(Host, ProcessRunner, ?array $catalog, bool $ask = true, bool $preselect = true)`.[^flow][^command-test]

Never writes `$shared['success']`: an extension that fails to build leaves the application created.

Machine access = two collaborators, both constructor arguments: [Host](/components/host.md) and [ProcessRunner](/components/process-runner.md). Tests pass `FakeHost` + `FakeProcessRunner` (`tests/Fakes/`) and press keys with `Prompt::fake()`.[^flow-test]

```
ExtensionOfferNode ── nothing installable / not interactive / declined: stop
        |  "find-php-config"
        v
PhpConfigFinderNode ── the only php-config builds for another PHP: stop
        |  "find-pie"
        v
PieFinderNode ──"install-pie"──> PieInstallNode ── declined / no home / download or verify failed: stop
        |  "select-extensions"          |  "select-extensions"
        v  <────────────────────────────┘
ExtensionSelectNode ── none selected: stop
        |  "install-extensions"
        v
ExtensionInstallNode
```

A stop = `post()` returns `null` and writes `$shared['extensions_note']`.

# Nodes

| Node | Does | Writes |
|---|---|---|
| `ExtensionOfferNode` | State per catalog entry: OS reason, else `installed` when `Host::loaded()`, else `not on Packagist at 0.10` when `Host::releases()` lists neither a tagged version nor the development branch on the entry's line, else `null` (installable). Asks `confirm('Install first-party PHP extensions?')` when `ask` | `extension_states` (value → reason or `null`); `extension_tokens` (value → what `pie install` takes); `extensions_selected` when `extension_only` named an installable one |
| `PhpConfigFinderNode` | Tries the php-config beside the binary (`php8.4` → `php-config8.4`), then the one on `PATH`. Takes the one whose `--php-binary` is the running binary (`realpath` on both) | `php_config` (path or `null`) |
| `PieFinderNode` | `Host::find('pie', [~/.local/bin])`, then `[php, pie, '--version']` must print `(PIE)` | `pie_binary` |
| `PieInstallNode` | Asks, downloads `PiePackage::PHAR_URL` to `~/.local/bin/pie`, runs `[php, pie, 'self-verify']`; removes a copy that fails | `pie_binary` |
| `ExtensionSelectNode` | [ChecklistPrompt](/components/checklist-prompt.md): all three listed, non-installable rows disabled with their reason, installable rows pre-selected | `extensions_selected` (values) |
| `ExtensionInstallNode` | Per selection: `[php, pie, 'install', value, '--with-php-config=…']` on the caller's terminal, then `[php, '--ri', name]` | `extension_results` (name → outcome) |

`php` = `Host::phpBinary()` = `PHP_BINARY`. PIE targets the PHP that runs it, so every PIE call is made through that binary.[^nodes][^pie]

# Rules

* **Order**: nothing-installable is checked before interactivity; a machine with everything installed is never asked anything.
* **Releases**: Packagist is asked only for an extension the OS allows and PHP does not load. Tagged `0.10.N` → token is the enum value (`pkg:^0.10`). No tag but a `0.10.x-dev` branch → token `pkg:0.10.x-dev`, the row ends `[0.10.x-dev]`, the outcome reads `installed (0.10.x-dev)`. Neither → disabled. No answer (offline, error) → token is the enum value; PIE then reports what it finds. Queried 2026-10-04: epoll, kqueue, pcurl, posi, ftdi tagged; appkit, gtk, fb, rasterize, imgdec on `0.10.x-dev`; qt not on Packagist.
* **Non-interactive**: PIE is never downloaded; `install:ext` without a name ends asking for one.
* **php-config**: found → passed as `--with-php-config`, pinning PIE's build to this PHP. None on the machine → flow goes on without the flag; PIE's own build-tool check offers PHP's development files. One exists but builds for another PHP → stop, note names both binaries.
* **PIE identity**: a `pie` that does not run under this PHP, or prints no `(PIE)`, counts as missing.
* **Install**: one `pie install` per extension (PIE takes one package per call). Run with `inheritTty` so PIE can ask for a sudo password or missing build tools; with no terminal, output goes to `output_callback`. A failure does not stop the next one.
* **Outcomes**: `installed` · `installed (0.10.x-dev)` · `failed (PIE exit code N)` · `installed by PIE, but {php} does not load it`.

# Notes (`extensions_note`)

| Stop | Note |
|---|---|
| Nothing installable | `Nothing to install: epoll (Linux only), kqueue (installed), pcurl (installed).` (each with its reason) |
| Non-interactive, `ask` | `Not offered: this run is not interactive.` |
| Non-interactive, no `ask`, no name | `Name the extension to install on a non-interactive run: {names}.` |
| Named: not in the catalog | `Unknown extension [x]. Choose one of: {names}.` |
| Named: not installable | `Nothing to install: {name} ({reason}).` |
| PIE missing, non-interactive | `PIE is not installed. Run this command interactively to install it.` |
| Offer declined | `Declined.` |
| Foreign php-config | `The php-config on this machine builds for {other}, not for {php}. Install the development files for {php} first.` |
| No home directory | `PIE is not installed, and there is no home directory to install it into.` |
| PIE declined | `PIE is not installed, and you declined to install it.` |
| Download failed | `PIE could not be downloaded from {url}.` |
| Verification failed | `The downloaded PIE failed its own verification and was removed.` |
| Empty selection | `None selected.` |

Every row above, every outcome, and each node's command lines have an `it()` in `ExtensionsFlowTest` or `InstallExtensionCommandTest`.[^flow-test][^command-test]

# Not covered by Pest

A real `pie install`, a real download and a real sudo prompt: the suite runs no PIE and no network.

# Related

- [Node orchestration](node-orchestration.md)
- [Host](/components/host.md), [ProcessRunner](/components/process-runner.md), [ChecklistPrompt](/components/checklist-prompt.md), [SummarizeExtensions](/components/summarize-extensions.md)
- [Enums](/components/enums.md) — `FirstPartyExtension`, `PiePackage`

[^flow]: ExtensionsFlow wiring
[^nodes]: The six nodes
[^flow-test]: Pest coverage for every path through the flow
[^command-test]: Pest coverage for install:ext over the whole catalog
[^pie]: PIE, the PHP Installer for Extensions

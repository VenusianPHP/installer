---
type: Architecture
title: ExtensionsFlow
description: The extension interview — offers a catalog of first-party extensions, installs the chosen ones through PIE run by the PHP binary running the installer. Used by new, install:ext, install:toolkit-ext and the toolkit SDK step.
resource: src/Workflows/Extensions/
tags: [core, pocketflow, flow, extensions, pie]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
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
    title: Pest coverage for install:ext and install:toolkit-ext
  - id: pie
    resource: https://github.com/php/pie
    title: PIE, the PHP Installer for Extensions
---

# What it is

A `Flow` of six nodes. Runs as one node of `new`, after create-project succeeds ([node orchestration](node-orchestration.md)). Standalone too: `(new ExtensionsFlow)->run($shared)` needs only `interactive` and `output_callback` in the bag.[^flow]

# Callers

| | `new` | `install:ext {ext?}` | `install:toolkit-ext {ext?}` | toolkit SDK step of `new` |
|---|---|---|---|---|
| Catalog | `FirstPartyExtension::core()` (epoll, kqueue, multicurl) | `core()` | `toolkits()` (appkit, gtk, qt, raven) | the selected toolkits' extensions |
| Asks first (`ask`) | yes: `Install first-party PHP extensions?` | no | no | no |
| List (`pick`) | yes, installable rows selected | yes, nothing selected | yes, nothing selected | no: every installable one is the selection |
| `extension_only` | never set | the `ext` argument | the `ext` argument | never set |
| Returns (`then`) | `offer-topside` | `null` | `null` | `null` |

Constructor: `ExtensionsFlow(Host, ProcessRunner, ?array $catalog, bool $ask = true, bool $preselect = true, bool $pick = true, ?string $then = null)`. However the flow ends, error included, it returns `then`: a nested PocketFlow `Flow` otherwise returns its last node's `null`, and the flow around it would stop.[^flow][^command-test]

Never writes `$shared['success']`: an extension that fails to build leaves the application created.

Machine access = two collaborators, both constructor arguments: [Host](/components/host.md) and [ProcessRunner](/components/process-runner.md). Tests pass `FakeHost` + `FakeProcessRunner` (`tests/Fakes/`) and press keys with `Prompt::fake()`.[^flow-test]

```
ExtensionOfferNode ── nothing installable / not interactive / declined: stop
        |  "find-php-config"
        v
PhpConfigFinderNode
        |  "find-pie"
        v
PieFinderNode ──"install-pie"──> PieInstallNode ── declined / no home / download or verify failed: stop
        |  "select-extensions"          |  "select-extensions"
        v  <────────────────────────────┘
ExtensionSelectNode ── none selected: stop  (no list with pick: false)
        |  "install-extensions"
        v
ExtensionInstallNode
```

A stop = `post()` returns `null` and writes `$shared['extensions_note']`.

# Nodes

| Node | Does | Writes |
|---|---|---|
| `ExtensionOfferNode` | State per catalog entry: OS reason, else `installed` when `Host::loaded()`, else `not on Packagist at 0.11` when `Host::releases()` lists neither a tagged version nor the development branch on the entry's line, else `null` (installable). Asks `confirm('Install first-party PHP extensions?')` when `ask` | `extension_states` (value → reason or `null`); `extension_tokens` (value → what `pie install` takes); `extensions_selected` when `extension_only` named an installable one |
| `PhpConfigFinderNode` | Tries `PHP_BINDIR/php-config`, the one beside the binary (`php8.4` → `php-config8.4`), then the one on `PATH`. Takes the first that builds for this PHP: its `--php-binary` is the running binary (`realpath` on both), or, when it prints `NONE` or nothing, its `--prefix` is `PHP_PREFIX`, its `--vernum` is `PHP_VERSION_ID` and its `--extension-dir` says `zts` exactly when this PHP is thread safe | `php_config` (path or `null`) |
| `PieFinderNode` | `Host::find('pie', [~/.local/bin])`, then `[php, pie, '--version']` must print `(PIE)` | `pie_binary` |
| `PieInstallNode` | Asks, downloads `PiePackage::PHAR_URL` to `~/.local/bin/pie`, runs `[php, pie, 'self-verify']`; removes a copy that fails | `pie_binary` |
| `ExtensionSelectNode` | [ChecklistPrompt](/components/checklist-prompt.md): the catalog listed, non-installable rows disabled with their reason, installable rows pre-selected when `preselect`. With `pick: false`, no list: every installable row | `extensions_selected` (values) |
| `ExtensionInstallNode` | Per selection: `[php, pie, 'install', value, '--with-php-config=…']` on the caller's terminal, then `[php, '--ri', name]` | `extension_results` (name → outcome) |

`php` = `Host::phpBinary()` = `PHP_BINARY`. PIE targets the PHP that runs it, so every PIE call is made through that binary.[^nodes][^pie]

# Rules

* **Order**: nothing-installable is checked before interactivity; a machine with everything installed is never asked anything.
* **Releases**: Packagist is asked only for an extension the OS allows and PHP does not load. Tagged `0.11.N` → token is the enum value (`pkg:^0.11`). No tag but a `0.11.x-dev` branch → token `pkg:0.11.x-dev`, the row ends `[0.11.x-dev]`, the outcome reads `installed (0.11.x-dev)`. Neither → disabled. No answer (offline, error) → token is the enum value; PIE then reports what it finds. Queried 2026-11-24: all seven tagged on 0.11 (epoll, kqueue, raven 0.11.0; multicurl 0.11.1; appkit, gtk, qt 0.11.2).
* **Non-interactive**: PIE is never downloaded; `install:ext` without a name ends asking for one.
* **php-config**: found → passed as `--with-php-config`, pinning PIE's build to this PHP. None builds for this PHP → no flag: PIE runs under this PHP and targets it, and its own build-tool check offers PHP's development files. Homebrew's `php@8.4-zts` is the case the prefix match exists for: built with only the embed SAPI listed, its php-config prints `--php-binary` `NONE`. Checked 2026-11-24 against Homebrew `php@8.4-zts`, `php@8.4` and `php` 8.5: each finds its own php-config.
* **PIE identity**: a `pie` that does not run under this PHP, or prints no `(PIE)`, counts as missing.
* **Install**: one `pie install` per extension (PIE takes one package per call). Run with `inheritTty` so PIE can ask for a sudo password or missing build tools; with no terminal, output goes to `output_callback`. A failure does not stop the next one.
* **Outcomes**: `installed` · `installed (0.11.x-dev)` · `failed (PIE exit code N)` · `installed by PIE, but {php} does not load it`.

# Notes (`extensions_note`)

| Stop | Note |
|---|---|
| Nothing installable | `Nothing to install: epoll (Linux only), kqueue (installed), multicurl (installed).` (each with its reason) |
| Non-interactive, `ask` | `Not offered: this run is not interactive.` |
| Non-interactive, no `ask`, no name | `Name the extension to install on a non-interactive run: {names}.` |
| Named: not in the catalog | `Unknown extension [x]. Choose one of: {names}.` |
| Named: not installable | `Nothing to install: {name} ({reason}).` |
| PIE missing, non-interactive | `PIE is not installed. Run this command interactively to install it.` |
| Offer declined | `Declined.` |
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
- [Host](/components/host.md), [ProcessRunner](/components/process-runner.md), [ChecklistPrompt](/components/checklist-prompt.md), [SummarizeSteps](/components/summarize-steps.md)
- [Enums](/components/enums.md) — `FirstPartyExtension`, `PiePackage`

[^flow]: ExtensionsFlow wiring
[^nodes]: The six nodes
[^flow-test]: Pest coverage for every path through the flow
[^command-test]: Pest coverage for install:ext and install:toolkit-ext
[^pie]: PIE, the PHP Installer for Extensions

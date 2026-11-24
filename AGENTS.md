# Agent guidelines — venusian/installer

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing installer code or advising on `venusian new`:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`. A concept re-checked against the tree may carry `verified` + `verification_key` (`<agent>@<commit>`) and `status: stable`; a concept an agent creates stays `status: draft` until a human verifies it.
5. Keep the `.okf` bundle at the **package root** only — do not nest extra `.okf` folders under `src/` or `tests/`.
6. Framework, skeleton, and probe knowledge belongs in those packages' own `.okf` bundles, not here.
7. The README carries a mermaid overview of the `new` flow; keep it in step with [.okf/core/node-orchestration.md](.okf/core/node-orchestration.md).

## Package rules (quick) — 0.11.0

- Composer: `venusian/installer` **0.11.0**. PHP `^8.4|^8.5|^8.6`. Global Composer CLI; bin is `bin/venusian` (Symfony Console app) with `new`, `install:ext`, `install:toolkit-ext`.
- `bin/venusian` passes the version to `Application`; it must equal `composer.json` `version` (`tests/BinaryTest.php` enforces it).
- From a checkout: `composer install` then `php bin/venusian` shows the command list.
- **One PHP binary.** Every process runs under `PHP_BINARY`: composer through `Host::phpCommand()` (`[PHP_BINARY, $path]` for a PHP script or phar), PIE, `php --ri`, the skeleton's `computer`, and the release watcher's update and re-exec. Never run a PHP script by its shebang.
- Orchestration uses `projectsaturnstudios/pocketflow-php`: `new` runs `NewApplicationFlow` = Start → ComposerFinder → ProjectCreation → ExtensionsFlow → TopsideOffer → TopsideInstall → ToolkitOffer → ToolkitSelect → ToolkitInstall. See [.okf/core/node-orchestration.md](.okf/core/node-orchestration.md) and [.okf/core/topside-flow.md](.okf/core/topside-flow.md).
- Every flow transition has a **named action**. PocketFlow reads a `null` return as `default`, so a node stops the flow by returning `null` and no node has a `default` successor. A nested flow returns its last node's action; `ExtensionsFlow` takes `then:` so the flow around it goes on.
- `ExtensionsFlow` (`src/Workflows/Extensions/`) installs `FirstPartyExtension` cases through PIE. `new` and `install:ext {ext?}` offer `FirstPartyExtension::core()` (epoll, kqueue, multicurl); `install:toolkit-ext {ext?}` offers `toolkits()` (appkit, gtk, qt, raven). It reaches the machine only through `Host` and `ProcessRunner`; tests swap both (`tests/Fakes/`).
- The toolkit SDK step installs each selected `Toolkit`'s extensions (GTK on macOS also needs appkit), then `composer require jovian/venusian-{toolkit}` only once they load.
- `new` creates `venusian/venusian:^0.11.1` via Composer (`--remove-vcs --prefer-dist`), then optionally requires `venusian/topside:^0.11` and runs `php computer vendor:publish --tag=surface-config`. It exits 0 once the application exists, 1 otherwise.
- CLI option / package maps live in **string-backed enums** under `src/Enums/` (FULLY UPPERCASE cases), not class constants.
- venusian/build (`install:sdk`) is decoupled from 0.11.0 and is to return later.
- Tests: Pest v4, no network, no real `composer` and no real `pie`. `tests/BinaryTest.php` runs `bin/venusian` locally with the update check off; `tests/Console/NewApplicationCommandTest.php` runs `new` against a stand-in `composer` on a private `PATH`, and with `FakeHost` / `FakeProcessRunner`. Prompts are driven with `Prompt::fake()`.

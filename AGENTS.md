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

## Package rules (quick) — 0.10.0

- Composer: `venusian/installer` **0.10.0**. PHP `^8.4|^8.5|^8.6`. Global Composer CLI; bin is `bin/venusian` (Symfony Console app).
- `bin/venusian` passes the version to `Application`; it must equal `composer.json` `version` (`tests/BinaryTest.php` enforces it).
- From a checkout: `composer install` then `php bin/venusian` shows the command list (`new` is registered).
- Orchestration uses `projectsaturnstudios/pocketflow-php`: `new` runs a Flow of Start → ComposerFinder → ProjectCreation. See [.okf/core/node-orchestration.md](.okf/core/node-orchestration.md).
- `new` creates `venusian/venusian:^0.10.0` via Composer (`--remove-vcs --prefer-dist`).
- CLI option / package maps live in **string-backed enums** under `src/Enums/` (FULLY UPPERCASE cases), not class constants.
- Prefer orchestrated setup (plan → stages) over Laravel-style linear setup scripts as features return.
- Tests: Pest v4, no network and no real `composer` except `tests/BinaryTest.php`, which runs `bin/venusian` locally with the update check off.

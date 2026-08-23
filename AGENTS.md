# Agent guidelines — venusian/installer

## Knowledge Bundle (OKF)

This package ships an Open Knowledge Format bundle at [`.okf/`](.okf/) (excluded from Composer dist via `.gitattributes` `export-ignore`).

Before changing installer code or advising on `venusian new` / post-create setup:

1. Read [`.okf/index.md`](.okf/index.md) first (progressive disclosure).
2. Open only the linked concepts needed for the task.
3. Prefer `status: stable` concepts; treat `deprecated` as historical only.
4. When you learn something durable about **this package**, update the affected `.okf` concept(s) and append `.okf/log.md`. New/changed concepts stay `status: draft` until a human verifies them.
5. Keep the `.okf` bundle at the **package root** only — do not nest extra `.okf` folders under `src/` or `tests/`.
6. Framework, GPIO, display, and native-binding knowledge belongs in those packages’ own docs / `.okf` bundles, not here.
7. Interactive logic-path canvas: [`docs/canvases/installer-logic-path.canvas.tsx`](docs/canvases/installer-logic-path.canvas.tsx) (`export-ignore` via `.gitattributes`). README embeds a mermaid overview for GitHub/Packagist readers.

## Package rules (quick)

- Global Composer CLI; bin is `bin/venusian` (Symfony Console app — not full Symfony HTTP).
- From a checkout: `composer install` then `php bin/venusian` shows the command list (`new` is registered).
- Orchestration uses `projectsaturnstudios/pocketflow-php`: `new` runs a Flow of Start → ComposerFinder → ProjectCreation. See [.okf/core/node-orchestration.md](.okf/core/node-orchestration.md).
- `new` creates `venusian/venusian:^0.8.2` via Composer (`--remove-vcs --prefer-dist`). 
- CLI option / package maps live in **string-backed enums** under `src/Enums/` (FULLY UPPERCASE cases), not class constants.
- Prefer orchestrated setup (plan → stages) over Laravel-style linear setup scripts as features return.

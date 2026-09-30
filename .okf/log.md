# Directory Update Log

## 2026-09-30

* **Update**: Bundle retargeted to **0.10.0** — skeleton pin `venusian/venusian:^0.10.0` in [package](orientation/package.md), [node orchestration](core/node-orchestration.md), [ComposerProjectCreator](components/composer-project-creator.md), [enums](components/enums.md), [new application](playbooks/new-application.md) (now lists what the skeleton's hooks do).
* **Update**: [Application](components/application.md) — `ApplicationTest` covers `doRun` order and arguments; `bin/venusian` section with `BinaryTest`. [Local development](playbooks/local-development.md) — 9 files, 36 `it()` cases.
* **Update**: Facts from the removed pages folded into their components — `USER_AGENT` literal ([enums](components/enums.md)), update-failure and PATH-miss messages ([PackagistReleaseWatcher](components/packagist-release-watcher.md)), unused constructor arguments and `execute()` returning 0 ([NewApplicationCommand](components/new-application-command.md)), `actions` unwritten ([PrepareSharedBag](components/prepare-shared-bag.md)).
* **Removal**: `traps/stale-branding.md`, `traps/release-watcher-network.md`, `known-gaps.md`.
* **Verification**: every remaining concept re-checked against the tree; `verification_key` `claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd`.

## 2026-08-23

* **Initialization**: Created OKF v0.2 bundle for `venusian/installer` **0.8.0** — orientation, PocketFlow node orchestration, components (`new` / creator / process runner / release watcher / resolve-directory / prepare-shared-bag / enums / application), new-application and local-development playbooks, stale-branding and release-watcher-network traps, known-gaps. All concepts `status: draft` pending human verification.
* **Note**: Source pin is `SkeletonPackage::VENUSIAN = venusian/venusian:^0.8.2`. `AGENTS.md` at `693b56c` already matches. Do not rewrite the enum to `^0.8.0`.
* **Note**: `PackagistReleaseWatcher` and `InstallerPackage::USER_AGENT` still emit ScrapyardIO / `scrapyard-io` literals. Recorded in `traps/stale-branding.md` (removed 2026-09-30), not presented as intended product copy.
* **Agent verification (framework-auditor)**: Tree-checked at `693b56c0cf3a622988e1f7b8147370aec9ba492f`.
  * `verification_key`: `agent:framework-auditor@693b56c0cf3a622988e1f7b8147370aec9ba492f`
  * `verified`: `{ by: agent:framework-auditor, at: 2026-08-23T17:31:14Z }`
  * Verified concepts set to `status: stable` (OKF v0.2: verified ⇒ stable). No `human:` stamp.
  * Corrections: `trim` vs `rtrim` on `new` name; skip-env `true` and WARN line are source-only; only `USER_AGENT` is Pest-pinned; `.gitattributes` measured list; `CHANGELOG.md` and `docs/` (canvas + mermaid) absent; Pest map 7 files / 33 `it()`; CI PHP 8.4/8.5.
* **Unchanged (measured true)**: package `venusian/installer` `0.8.0`, PHP `^8.4|^8.5|^8.6`, bin `bin/venusian`, ns `Venusian\Installer\`; skeleton `venusian/venusian:^0.8.2`; flow Start → ComposerFinder → ProjectCreation; `actions => []`; enums FULLY UPPERCASE; README `# Laravel Installer`; unused command ctor deps; `execute()` always `0`; Pest v4 (`^4`, resolved `v4.7.8` on the audit machine).

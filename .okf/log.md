# Directory Update Log

## 2026-08-23

* **Initialization**: Created OKF v0.2 bundle for `venusian/installer` **0.8.0** — orientation, PocketFlow node orchestration, components (`new` / creator / process runner / release watcher / resolve-directory / prepare-shared-bag / enums / application), new-application and local-development playbooks, stale-branding and release-watcher-network traps, known-gaps. All concepts `status: draft` pending human verification.
* **Note**: Source pin is `SkeletonPackage::VENUSIAN = venusian/venusian:^0.8.2`. AGENTS.md previously said `^0.8.0` (drift recorded in [known gaps](/known-gaps.md) and corrected in the same pass).
* **Note**: `PackagistReleaseWatcher` and `InstallerPackage::USER_AGENT` still emit ScrapyardIO / `scrapyard-io` literals. Recorded in [stale branding](/traps/stale-branding.md), not presented as intended product copy.

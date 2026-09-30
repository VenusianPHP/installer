---
type: Module
title: ProcessRunner
description: Thin Symfony Process wrapper — run/succeeds/output with an injectable process factory.
resource: src/ProcessRunner.php
tags: [component, process]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
status: stable
sources:
  - id: runner
    resource: src/ProcessRunner.php
    title: ProcessRunner
  - id: runner-test
    resource: tests/ProcessRunnerTest.php
    title: Pest coverage for run / succeeds / output
---

# Role

Used by [PackagistReleaseWatcher](packagist-release-watcher.md) for `composer global require` and re-exec. Constructor `processFactory` is injectable so tests never spawn a real process.[^runner][^runner-test]

# Behavior (tested)

| Method | Result |
|--------|--------|
| `run(...)` | `$process->getExitCode()`, or `1` when the exit code is `null` |
| `succeeds(...)` | `true` iff `run(...) === 0` |
| `output(...)` | `trim($process->getOutput())` when exit code is `0`; `null` when exit code is `null` or non-zero |

`run()` forwards `$outputCallback` unless `inheritTty` is true **and** TTY attach succeeds. TTY attach (`canInheritTty` / `setTty`) has no Pest test; when it fails, `run()` falls back to the callback path.

# Related

- [PackagistReleaseWatcher](packagist-release-watcher.md)
- [ComposerProjectCreator](composer-project-creator.md)

[^runner]: ProcessRunner
[^runner-test]: Pest coverage for run / succeeds / output

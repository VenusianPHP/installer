---
type: Module
title: ResolveDirectory
description: Maps the `new` name argument to `.`, an absolute path, or `cwd + DIRECTORY_SEPARATOR + name`.
resource: src/Actions/ResolveDirectory.php
tags: [component, action, path]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: resolve
    resource: src/Actions/ResolveDirectory.php
    title: ResolveDirectory::run
  - id: resolve-test
    resource: tests/Actions/ResolveDirectoryTest.php
    title: Pest coverage for passthrough and relative join
---

# Behavior (tested)

`ResolveDirectory::run(string $name): string`:[^resolve][^resolve-test]

| Input | Result |
|-------|--------|
| `'.'` | `'.'` (passthrough) |
| Absolute path (`Filesystem::isAbsolutePath`) | `$name` unchanged |
| Relative name | `getcwd() . DIRECTORY_SEPARATOR . $name` |

If `getcwd()` is `false`, the method returns `$name` unchanged. That branch is not in the Pest map.

# Related

- [NewApplicationCommand](new-application-command.md)

[^resolve]: ResolveDirectory::run
[^resolve-test]: Pest coverage for passthrough and relative join

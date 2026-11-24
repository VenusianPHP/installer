---
type: Module
title: Host
description: The machine the installer runs on and the PHP binary running it — binary path and build identity, how to run a PHP script under it, OS family, loaded extensions, home, executable lookup, download.
resource: src/Host.php
tags: [component, host, extensions]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
sources:
  - id: host
    resource: src/Host.php
    title: Host
  - id: host-test
    resource: tests/HostTest.php
    title: Pest coverage for every method
  - id: fake
    resource: tests/Fakes/FakeHost.php
    title: The test double
---

# Role

Everything the flows ([ExtensionsFlow](/core/extensions-flow.md), [node orchestration](/core/node-orchestration.md), [Topside flow](/core/topside-flow.md)) and the [release watcher](packagist-release-watcher.md) ask of the machine that is not a process. Not final: `FakeHost` extends it and answers from its constructor arguments.[^host][^fake]

# Behavior (tested)

| Method | Result |
|---|---|
| `phpBinary()` | `PHP_BINARY` |
| `phpBindir()`, `phpPrefix()`, `phpVersionId()`, `threadSafe()` | `PHP_BINDIR`, `PHP_PREFIX`, `PHP_VERSION_ID`, `(bool) PHP_ZTS` (a `bool` on Homebrew 8.4-zts, so never compared with `1`): what a php-config for this PHP reports |
| `phpCommand($script)` | `[PHP_BINARY, $script]` when the script ends `.phar`, starts `<?php`, or has a shebang naming `php`; `[$script]` otherwise (a shell wrapper, a missing file) |
| `osFamily()` | `PHP_OS_FAMILY` |
| `loaded($extension)` | `extension_loaded()` in the running process |
| `home()` | `HOME`; `null` when unset or empty |
| `find($name, $extra_directories)` | Symfony `ExecutableFinder`: `PATH`, then the extra directories; `null` on a miss |
| `download($url, $path)` | Creates the directory, copies the URL to the path, `chmod 0755`. `false` on any I/O failure, and no file is left |
| `remove($path)` | Deletes the file |
| `releases($package)` | Versions Packagist lists (`repo.packagist.org/p2`, tagged file then `~dev` file), leading `v` stripped; `[]` on 404; `null` when Packagist cannot be asked. No Pest test: the suite uses no network, `FakeHost` overrides it |

All but `releases()`: `tests/HostTest.php`. `download()` is tested with a `file://` URL; no test fetches over the network.[^host-test]

# Related

- [ExtensionsFlow](/core/extensions-flow.md)
- [ProcessRunner](process-runner.md)

[^host]: Host
[^host-test]: Pest coverage for every method
[^fake]: The test double

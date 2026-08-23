---
type: Playbook
title: New application
description: Scaffold a Venusian PHP app with `venusian new` and optionally skip the installer self-update check.
tags: [playbook, new, cli]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "agent:framework-auditor", at: "2026-08-23T17:31:14Z" }
verification_key: "agent:framework-auditor@693b56c0cf3a622988e1f7b8147370aec9ba492f"
status: stable
sources:
  - id: command
    resource: src/Console/Commands/NewApplicationCommand.php
    title: new command argument
  - id: enums
    resource: src/Enums/InstallerPackage.php
    title: NO_UPDATE_CHECK_ENV
  - id: skeleton
    resource: src/Enums/SkeletonPackage.php
    title: Skeleton pin
  - id: package
    resource: .okf/orientation/package.md
    title: Package orientation
---

# Install the CLI

```bash
composer global require venusian/installer:^0.8.0
```

The bin is `venusian` (`InstallerPackage::BINARY`).[^enums][^package]

# Scaffold

```bash
venusian new example-app
```

`name` is required. `.` means the current directory; a relative name is joined to `getcwd()`. The command `trim`s `/` and `\` from both ends of `name` before resolving the directory. See [ResolveDirectory](/components/resolve-directory.md).[^command]

`create-project` targets `venusian/venusian:^0.8.2` with `--remove-vcs --prefer-dist`.[^skeleton]

# Disable the update check

Non-interactive runs skip the watcher. To skip it in an interactive session:

```bash
VENUSIAN_INSTALLER_NO_UPDATE_CHECK=1 venusian new example-app
```

Env name is `InstallerPackage::NO_UPDATE_CHECK_ENV`.[^enums] See [release-watcher network](/traps/release-watcher-network.md).

# Related

- [NewApplicationCommand](/components/new-application-command.md)
- [Node orchestration](/core/node-orchestration.md)
- [Local development](local-development.md)

[^command]: new command argument
[^enums]: NO_UPDATE_CHECK_ENV
[^skeleton]: Skeleton pin
[^package]: Package orientation

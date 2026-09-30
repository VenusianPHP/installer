---
type: Playbook
title: New application
description: Scaffold a Venusian PHP app with `venusian new` and optionally skip the installer self-update check.
tags: [playbook, new, cli]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
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
composer global require venusian/installer
```

The bin is `venusian` (`InstallerPackage::BINARY`).[^enums][^package]

# Scaffold

```bash
venusian new example-app
```

`name` is required. `.` means the current directory; a relative name is joined to `getcwd()`. The command `trim`s `/` and `\` from both ends of `name` before resolving the directory. See [ResolveDirectory](/components/resolve-directory.md).[^command]

`create-project` targets `venusian/venusian:^0.10.0` with `--remove-vcs --prefer-dist`.[^skeleton] The skeleton's hooks copy `.env.example` → `.env`, write `APP_KEY`, create `database/database.sqlite`, and run `package:discover`; `php rocket hello-world` then prints `Hello, world.`

# Disable the update check

Non-interactive runs skip the watcher. To skip it in an interactive session:

```bash
VENUSIAN_INSTALLER_NO_UPDATE_CHECK=1 venusian new example-app
```

Env name is `InstallerPackage::NO_UPDATE_CHECK_ENV`.[^enums] Skip rules: [PackagistReleaseWatcher](/components/packagist-release-watcher.md).

# Related

- [NewApplicationCommand](/components/new-application-command.md)
- [Node orchestration](/core/node-orchestration.md)
- [Local development](local-development.md)

[^command]: new command argument
[^enums]: NO_UPDATE_CHECK_ENV
[^skeleton]: Skeleton pin
[^package]: Package orientation

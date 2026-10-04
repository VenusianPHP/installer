---
type: Playbook
title: New application
description: Scaffold a Venusian PHP app with `venusian new`, install the first-party PHP extensions, and optionally skip the installer self-update check.
tags: [playbook, new, cli]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
verification_key: "claude-fable-5-1@6689f865c8b40863d13dc520135c0217579d3224"
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

`name` is required. `.` means the current directory; a relative name is joined to `getcwd()`. An absolute path is used as given. The command `rtrim`s `/` and `\` from `name` before resolving the directory. See [ResolveDirectory](/components/resolve-directory.md).[^command]

`create-project` targets `venusian/venusian:^0.10.0` with `--remove-vcs --prefer-dist`.[^skeleton] The skeleton's hooks copy `.env.example` → `.env`, write `APP_KEY`, create `database/database.sqlite`, and run `package:discover`; `php rocket hello-world` then prints `Hello, world.`

# PHP extensions

After the app exists, an interactive run asks `Install first-party PHP extensions?`. Yes → a list of epoll, kqueue, pcurl; rows for another OS or already loaded are disabled. Selected ones are built by PIE for the PHP binary running `venusian`.

* PIE missing → asks, downloads `pie.phar` to `~/.local/bin/pie`, PIE verifies itself.
* Needs a C compiler and PHP's development files (`phpize`, `php-config`); PIE offers to install missing build tools.
* PIE asks for a sudo password when PHP's extension directory is not writable.
* Outcome per extension appears in the `Installation Successful!` box. A failed build leaves the app in place.
* Non-interactive run, a `No`, or nothing left to install → step skipped, reason in the box.

By hand, for one extension:

```bash
php "$(command -v pie)" install php-io-extensions/pcurl:^0.10
```

Detail: [ExtensionsFlow](/core/extensions-flow.md).

# Disable the update check

Non-interactive runs skip the watcher. To skip it in an interactive session:

```bash
VENUSIAN_INSTALLER_NO_UPDATE_CHECK=1 venusian new example-app
```

Env name is `InstallerPackage::NO_UPDATE_CHECK_ENV`.[^enums] Skip rules: [PackagistReleaseWatcher](/components/packagist-release-watcher.md).

# Related

- [NewApplicationCommand](/components/new-application-command.md)
- [Node orchestration](/core/node-orchestration.md)
- [ExtensionsFlow](/core/extensions-flow.md)
- [Local development](local-development.md)

[^command]: new command argument
[^enums]: NO_UPDATE_CHECK_ENV
[^skeleton]: Skeleton pin
[^package]: Package orientation

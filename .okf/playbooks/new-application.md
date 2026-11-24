---
type: Playbook
title: New application
description: Scaffold a Venusian PHP app with `venusian new`, install the first-party PHP extensions, Topside and the toolkit SDKs, and optionally skip the installer self-update check.
tags: [playbook, new, cli]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
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

`create-project` targets `venusian/venusian:^0.11.1` with `--remove-vcs --prefer-dist`.[^skeleton] Composer runs under the PHP binary running `venusian`, so the app's platform packages and the skeleton's `@php` hooks are that PHP's. The hooks copy `.env.example` → `.env`, write `APP_KEY`, create `database/database.sqlite`, and run `package:discover`.

To use a PHP other than the one on `PATH`, run the installer with it:

```bash
/opt/homebrew/opt/php@8.4-zts/bin/php "$(command -v venusian)" new example-app
```

# PHP extensions

After the app exists, an interactive run asks `Install first-party PHP extensions?`. Yes → a list of epoll, kqueue, multicurl; rows for another OS or already loaded are disabled. Selected ones are built by PIE for the PHP binary running `venusian`, with that PHP's own php-config.

* PIE missing → asks, downloads `pie.phar` to `~/.local/bin/pie`, PIE verifies itself.
* Needs a C compiler and PHP's development files (`phpize`, `php-config`); PIE offers to install missing build tools.
* PIE asks for a sudo password when PHP's extension directory is not writable.
* Outcome per extension appears in the `Installation Successful!` box. A failed build leaves the app in place.
* Non-interactive run, a `No`, or nothing left to install → step skipped, reason in the box.

Later: `venusian install:ext [epoll|kqueue|multicurl]`.

# Topside and toolkit SDKs

Next the run asks `Install venusian/topside?`. Yes → `composer require venusian/topside:^0.11` in the app, then `php computer vendor:publish --tag=surface-config` (writes `config/toolkits.php`, `config/main.presentation.php`).

Then `Install toolkit SDKs?`. Yes → a list of appkit, gtk, qt, raven, nothing selected; appkit is disabled off macOS. Selecting none is fine. For each one selected, its extension is built by PIE (GTK on macOS also gets ext-appkit), then `composer require jovian/venusian-{toolkit}:^0.11`. An adapter whose extension does not load is not required; the box says why.

Later, extensions only: `venusian install:toolkit-ext [appkit|gtk|qt|raven]`. Detail: [Topside flow](/core/topside-flow.md), [ExtensionsFlow](/core/extensions-flow.md).

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

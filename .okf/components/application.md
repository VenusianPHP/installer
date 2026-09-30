---
type: Module
title: Application
description: Symfony Console application that offers a Packagist self-update before dispatching commands.
resource: src/Console/Application.php
tags: [component, console]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
status: stable
sources:
  - id: app
    resource: src/Console/Application.php
    title: Venusian\\Installer\\Console\\Application
  - id: bin
    resource: bin/venusian
    title: Entrypoint constructs Application 0.10.0
  - id: watcher
    resource: src/ReleaseChannel/PackagistReleaseWatcher.php
    title: maybeOfferUpdate
  - id: watcher-test
    resource: tests/ReleaseChannel/PackagistReleaseWatcherTest.php
    title: Pest coverage for the watcher itself
  - id: app-test
    resource: tests/Console/ApplicationTest.php
    title: Pest coverage for doRun ordering and arguments
  - id: bin-test
    resource: tests/BinaryTest.php
    title: Pest coverage for bin/venusian version and commands
---

# Role

`Venusian\Installer\Console\Application` extends `Symfony\Component\Console\Application`.[^app]

`bin/venusian` constructs `new Application('Venusian PHP Application Installer', '0.10.0')` and registers [NewApplicationCommand](new-application-command.md).[^bin]

# doRun

Reads `$_SERVER['argv'] ?? []`, then:[^app][^watcher]

```
$this->releaseWatcher->maybeOfferUpdate($input, $output, $argv, $this->getVersion());
return parent::doRun($input, $output);
```

`offers an update with argv and its own version before running the command` asserts the watcher gets `$_SERVER['argv']` and the app version, and runs before the command.[^app-test] Watcher behavior (offer, skip, cache): `PackagistReleaseWatcherTest`.[^watcher-test]

# bin/venusian

Loads the global Composer autoloader when installed globally (`../../../autoload.php`), else the checkout's `vendor/autoload.php`. Adds `NewApplicationCommand`; a throw prints its message to STDERR, exit 1. `reports the version composer.json declares from bin/venusian` and `registers the new command in bin/venusian` run it with the update check off.[^bin][^bin-test]

The watcher is constructor-injectable (`?PackagistReleaseWatcher $releaseWatcher = null`).

# Related

- [PackagistReleaseWatcher](packagist-release-watcher.md)
- [NewApplicationCommand](new-application-command.md)
- [Package](/orientation/package.md)

[^app]: Venusian\\Installer\\Console\\Application
[^bin]: Entrypoint constructs Application 0.10.0
[^watcher]: maybeOfferUpdate
[^watcher-test]: Pest coverage for the watcher itself
[^app-test]: Pest coverage for doRun ordering and arguments
[^bin-test]: Pest coverage for bin/venusian version and commands

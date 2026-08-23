---
type: Module
title: Application
description: Symfony Console application that offers a Packagist self-update before dispatching commands.
resource: src/Console/Application.php
tags: [component, console]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: app
    resource: src/Console/Application.php
    title: Venusian\\Installer\\Console\\Application
  - id: bin
    resource: bin/venusian
    title: Entrypoint constructs Application 0.8.0
  - id: watcher
    resource: src/ReleaseChannel/PackagistReleaseWatcher.php
    title: maybeOfferUpdate
  - id: watcher-test
    resource: tests/ReleaseChannel/PackagistReleaseWatcherTest.php
    title: Pest coverage for the watcher hooked from doRun
---

# Role

`Venusian\Installer\Console\Application` extends `Symfony\Component\Console\Application`.[^app]

`bin/venusian` constructs `new Application('Venusian PHP Application Installer', '0.8.0')` and registers [NewApplicationCommand](new-application-command.md).[^bin]

# doRun

Reads `$_SERVER['argv'] ?? []`, then:[^app][^watcher]

```
$this->releaseWatcher->maybeOfferUpdate($input, $output, $argv, $this->getVersion());
return parent::doRun($input, $output);
```

Watcher behavior (offer, skip, cache) is covered by `PackagistReleaseWatcherTest`, not a separate Application test.[^watcher-test]

The watcher is constructor-injectable (`?PackagistReleaseWatcher $releaseWatcher = null`).

# Related

- [PackagistReleaseWatcher](packagist-release-watcher.md)
- [NewApplicationCommand](new-application-command.md)
- [Package](/orientation/package.md)

[^app]: Venusian\\Installer\\Console\\Application
[^bin]: Entrypoint constructs Application 0.8.0
[^watcher]: maybeOfferUpdate
[^watcher-test]: Pest coverage for the watcher hooked from doRun

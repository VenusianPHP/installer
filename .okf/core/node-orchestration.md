---
type: Architecture
title: Node orchestration
description: PocketFlow wiring for venusian new — Start → ComposerFinder → ProjectCreation → ExtensionsFlow → Topside → toolkit SDKs over a shared bag; named actions, null stops.
resource: src/Workflows/NewApplication/NewApplicationFlow.php
tags: [core, pocketflow, flow, new]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
sources:
  - id: flow
    resource: src/Workflows/NewApplication/NewApplicationFlow.php
    title: NewApplicationFlow wiring
  - id: bag
    resource: src/Actions/PrepareSharedBag.php
    title: Initial shared bag keys
  - id: start
    resource: src/Workflows/NewApplication/NewApplicationStartNode.php
    title: Start node post()
  - id: finder
    resource: src/Workflows/Misc/ComposerFinderNode.php
    title: ComposerFinderNode
  - id: create
    resource: src/Workflows/NewApplication/ProjectCreationNode.php
    title: ProjectCreationNode
  - id: flow-test
    resource: tests/Workflows/NewApplicationFlowNodesTest.php
    title: Pest coverage for start / finder / creation nodes
  - id: command-test
    resource: tests/Console/NewApplicationCommandTest.php
    title: Pest coverage for the wired flow, end to end
  - id: bag-test
    resource: tests/Actions/PrepareSharedBagTest.php
    title: Pest coverage for shared bag keys
---

# What it is

`new` runs `NewApplicationFlow($directory, Host, ProcessRunner)`, a PocketFlow `Flow`, over the shared bag.[^flow]

```
NewApplicationStartNode
        |  "find-composer"
        v
ComposerFinderNode ── miss: null, flow stops
        |  "create-project"
        v
ProjectCreationNode ── non-zero exit: null, flow stops
        |  "offer-extensions"
        v
ExtensionsFlow   (a Flow run as one node; always returns "offer-topside")
        |  "offer-topside"
        v
TopsideOfferNode → TopsideInstallNode → ToolkitOfferNode → ToolkitSelectNode → ToolkitInstallNode
```

[ExtensionsFlow](extensions-flow.md) = the extension step. [Topside flow](topside-flow.md) = Topside and the toolkit SDKs. Everything after `ProjectCreationNode` is optional: each step ends with a note in the bag and the application stays.

# One PHP binary

Every process `new` starts runs under the PHP binary running the installer (`Host::phpBinary()` = `PHP_BINARY`):

* Composer: `ComposerFinderNode` turns the `composer` found on `PATH` into `Host::phpCommand($path)` = `[PHP_BINARY, $path]` for a PHP script or phar, so its `#!/usr/bin/env php` shebang never picks another PHP. Composer then resolves `php` / `ext-*` platform packages, and runs the skeleton's `@php` scripts, for that PHP. `it runs a PHP composer under the PHP binary running the installer, not the one its shebang finds` holds it.[^finder][^command-test]
* PIE, `php --ri`, the skeleton's `computer`: run as `[PHP_BINARY, …]`.

# Rule: named actions, null stops

PocketFlow `Flow::get_next_node`: a `post()` return that is not a string is looked up as `'default'`. So:

* Every transition gets its own action name.
* A node stops the flow by returning `null`.
* No node has a `'default'` successor.
* A nested `Flow` returns its last node's action, which is `null` at every stop. `ExtensionsFlow` takes `then:` and returns it however it ends, so `new` goes on to Topside.

# Shared bag

[PrepareSharedBag](/components/prepare-shared-bag.md) seeds the bag.[^bag][^bag-test]

| Key | Initial | Who writes later |
|-----|---------|------------------|
| `success` | `null` | Finder miss → `false`. Creation `post` → `exit === 0`. No optional step writes it. |
| `name` | `name` argument, trailing `/` `\` trimmed | — |
| `actions` | `[]` | No node writes it. |
| `interactive` | `$input->isInteractive()` | Read by the offer nodes. |
| `output_callback` | writes process buffer to `$output` | Passed to every process that does not take the terminal. |
| `directory` | unset | Start `post`. |
| `composer_command` | unset | Finder on hit: the command that runs composer. |
| `errors` | unset | Finder miss or creation failure. |
| `extension_*`, `extensions_*`, `php_config`, `pie_binary` | unset | [ExtensionsFlow](extensions-flow.md). |
| `topside_*`, `toolkit*` | unset | [Topside flow](topside-flow.md). |

# Node contracts (tested)

## NewApplicationStartNode::post

Sets `$shared['directory']`, returns `'find-composer'`.[^start][^flow-test]

## ComposerFinderNode

- Hit: `Host::find('composer')`, sets `$shared['composer_command'] = Host::phpCommand($path)`, returns `'create-project'`.[^finder][^flow-test]
- Miss: `$shared['success'] = false`, `$shared['errors'] = ['composer' => 'Could not find the composer executable.']`, returns `null`.[^finder][^flow-test][^command-test]

## ProjectCreationNode

Runs through `ProcessRunner`:[^create][^flow-test]

```
[...composer_command, 'create-project', 'venusian/venusian:^0.11.1', directory, '--remove-vcs', '--prefer-dist']
```

with `output_callback`. `$shared['success'] = ($exit === 0)`.

- Success: returns `'offer-extensions'`.
- Failure: `$shared['errors'] = ['message' => Process::$exitCodes[$exit] ?? 'Unknown error', 'code' => $exit]`, returns `null`. The extension step does not run (`exits 1 and does not reach the extension step when create-project fails`).[^command-test]

# Related

- [ExtensionsFlow](extensions-flow.md)
- [Topside flow](topside-flow.md)
- [NewApplicationCommand](/components/new-application-command.md)
- [PrepareSharedBag](/components/prepare-shared-bag.md)

[^flow]: NewApplicationFlow wiring
[^bag]: Initial shared bag keys
[^start]: Start node post()
[^finder]: ComposerFinderNode
[^create]: ProjectCreationNode
[^flow-test]: Pest coverage for start / finder / creation nodes
[^command-test]: Pest coverage for the wired flow, end to end
[^bag-test]: Pest coverage for shared bag keys

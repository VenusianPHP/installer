---
type: Architecture
title: Node orchestration
description: PocketFlow wiring for venusian new — Start → ComposerFinder → ProjectCreation over a shared bag.
resource: src/Console/Commands/NewApplicationCommand.php
tags: [core, pocketflow, flow, new]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: command
    resource: src/Console/Commands/NewApplicationCommand.php
    title: Flow construction in NewApplicationCommand::execute
  - id: bag
    resource: src/Actions/PrepareSharedBag.php
    title: Initial shared bag keys
  - id: start
    resource: src/Workflows/NewApplication/NewApplicationStartNode.php
    title: Start node post()
  - id: finder
    resource: src/Workflows/Misc/ComposerFinderNode.php
    title: ComposerFinderNode post()
  - id: create
    resource: src/Workflows/NewApplication/ProjectCreationNode.php
    title: ProjectCreationNode prep/post
  - id: flow-test
    resource: tests/Workflows/NewApplicationFlowNodesTest.php
    title: Pest coverage for start / finder / creation nodes
  - id: bag-test
    resource: tests/Actions/PrepareSharedBagTest.php
    title: Pest coverage for shared bag keys
---

# What it is

`new` does **not** run a plan → `$shared['actions']` → execute pipeline. It builds a PocketFlow `Flow` of three nodes and calls `run($shared)`.[^command]

```
NewApplicationStartNode
        |  action "find-composer"
        v
ComposerFinderNode
        |  action "default" (on hit)
        v
ProjectCreationNode
```

Wiring in `NewApplicationCommand::execute`:[^command]

1. `$start_node->next($finder_node, 'find-composer')`
2. `$finder_node->next($install_node)` (PocketFlow default action)
3. `new Flow($start_node)->run($shared)`

# Shared bag

[PrepareSharedBag](/components/prepare-shared-bag.md) seeds the bag before the flow runs.[^bag][^bag-test]

| Key | Initial | Who writes later |
|-----|---------|------------------|
| `success` | `null` | Finder miss → `false`. Creation `post` → `exit === 0`. |
| `name` | trimmed `name` argument | — |
| `actions` | `[]` | Nobody in this reconstitution. See [known gaps](/known-gaps.md). |
| `interactive` | `$input->isInteractive()` | — |
| `output_callback` | writes process buffer to `$output` | Passed through creation `prep`. |
| `directory` | unset | Start `post` sets it from the node constructor. |
| `composer_binary` | unset | Finder `post` on hit. |
| `errors` | unset | Finder miss or creation failure. |

# Node contracts (tested)

## NewApplicationStartNode::post

Sets `$shared['directory']` to the constructor directory and returns `'find-composer'`.[^start][^flow-test]

## ComposerFinderNode::post

- Hit (`$exec_res` not null): sets `$shared['composer_binary']` and returns `'default'`.[^finder][^flow-test]
- Miss (`$exec_res` null): sets `$shared['success'] = false` and `$shared['errors'] = ['composer' => 'Could not find the composer executable.']`, returns `null` (flow stops).[^finder][^flow-test]

`exec()` calls `new ExecutableFinder()->find('composer')` — not injected. Tests drive `post()` with a synthetic `$exec_res`.

## ProjectCreationNode::prep

Builds:[^create][^flow-test]

```
[
  composer_binary,
  'create-project',
  'venusian/venusian:^0.8.2',
  directory,
  '--remove-vcs',
  '--prefer-dist',
]
```

plus a `Process` factory and the shared `output_callback`.

## ProjectCreationNode::post

`$shared['success'] = ($exec_res->getExitCode() === 0)`. On failure, `$shared['errors']` is `['message' => getExitCodeText(), 'code' => getExitCode()]`.[^create][^flow-test]

# Related

- [NewApplicationCommand](/components/new-application-command.md)
- [PrepareSharedBag](/components/prepare-shared-bag.md)
- [ComposerProjectCreator](/components/composer-project-creator.md) — same argv shape, unused by this flow today

[^command]: Flow construction in NewApplicationCommand::execute
[^bag]: Initial shared bag keys
[^start]: Start node post()
[^finder]: ComposerFinderNode post()
[^create]: ProjectCreationNode prep/post
[^flow-test]: Pest coverage for start / finder / creation nodes
[^bag-test]: Pest coverage for shared bag keys

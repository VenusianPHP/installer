---
type: Module
title: NewApplicationCommand
description: Symfony Console command `new` that scaffolds a Venusian app via PocketFlow.
resource: src/Console/Commands/NewApplicationCommand.php
tags: [component, command, new]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-opus-5-5", at: "2026-09-30T00:00:00Z" }
verification_key: "claude-opus-5-5@1ce46f6f838641a45ffe4ab85272ad821b312ecd"
status: stable
sources:
  - id: command
    resource: src/Console/Commands/NewApplicationCommand.php
    title: NewApplicationCommand
  - id: flow-test
    resource: tests/Workflows/NewApplicationFlowNodesTest.php
    title: Pest coverage for the nodes the command wires
  - id: resolve-test
    resource: tests/Actions/ResolveDirectoryTest.php
    title: Pest coverage for the name → directory step
---

# Surface

`#[AsCommand(name: 'new', description: 'Create a new Venusian PHP application')]`.[^command]

| Piece | Value |
|-------|-------|
| Name | `new` |
| Argument | `name` — `InputArgument::REQUIRED` — "The name (or path) of the application" |

`execute()` passes `trim((string) $input->getArgument('name'), '/\\')` (both ends) into [ResolveDirectory](resolve-directory.md). [PrepareSharedBag](prepare-shared-bag.md) stores `rtrim(..., '/\\')` (trailing only) as `$shared['name']`.[^command][^resolve-test]

It then [PrepareSharedBag](prepare-shared-bag.md), wires the [node flow](/core/node-orchestration.md), and `Flow::run($shared)`.[^command][^flow-test]

# Callout outcomes

After the flow, the command inspects `$shared['success']`:[^command]

| `$shared['success']` | Callout |
|----------------------|---------|
| `true` | `Installation Successful!` — "Application ready at {$directory}" |
| `false` | `Installation Failed` (type `error`) — key/value list of `$shared['errors']` (fallback `"Unknown" => "No Message Provided"`) |
| other (including initial `null`) | `Installation Incomplete` (type `warning`) |
| key missing | `Installation Returned Malformed Response` (type `warn`) |

`execute()` returns `0` after every callout, failure included. No Pest test covers `execute()`: it builds its nodes itself, and `ComposerFinderNode` runs a real `ExecutableFinder`.

The constructor takes optional `ComposerProjectCreator` and `Filesystem`; `execute()` uses neither (creation runs inside `ProjectCreationNode`).

# Related

- [Node orchestration](/core/node-orchestration.md)
- [ResolveDirectory](resolve-directory.md)
- [PrepareSharedBag](prepare-shared-bag.md)
- [Playbook: new application](/playbooks/new-application.md)

[^command]: NewApplicationCommand
[^flow-test]: Pest coverage for the nodes the command wires
[^resolve-test]: Pest coverage for the name → directory step

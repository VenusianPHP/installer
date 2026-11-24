---
type: Module
title: NewApplicationCommand
description: Symfony Console command `new` that scaffolds a Venusian app via PocketFlow, then offers the PHP extensions, Topside and the toolkit SDKs.
resource: src/Console/Commands/NewApplicationCommand.php
tags: [component, command, new]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
sources:
  - id: command
    resource: src/Console/Commands/NewApplicationCommand.php
    title: NewApplicationCommand
  - id: flow-test
    resource: tests/Workflows/NewApplicationFlowNodesTest.php
    title: Pest coverage for the nodes the command wires
  - id: command-test
    resource: tests/Console/NewApplicationCommandTest.php
    title: Pest coverage for execute(), end to end
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

`execute()` passes `rtrim((string) $input->getArgument('name'), '/\\')` (trailing only) into [ResolveDirectory](resolve-directory.md), so an absolute path keeps its leading slash. [PrepareSharedBag](prepare-shared-bag.md) stores the same value as `$shared['name']`.[^command][^resolve-test][^command-test]

It then [PrepareSharedBag](prepare-shared-bag.md) and runs `NewApplicationFlow($directory, $host, $runner)` ([node orchestration](/core/node-orchestration.md)). The constructor takes an optional `Host` and `ProcessRunner`, which tests replace with `FakeHost` and `FakeProcessRunner`.[^command][^flow-test]

# Callout outcomes

After the flow, the command inspects `$shared['success']`:[^command]

| `$shared['success']` | Callout |
|----------------------|---------|
| `true` | `Installation Successful!` — "Application ready at {$directory}", then [SummarizeSteps](summarize-steps.md) (PHP extensions, Topside, toolkit SDKs: outcomes, or why each step ended) |
| `false` | `Installation Failed` (type `error`) — key/value list of `$shared['errors']` (fallback `"Unknown" => "No Message Provided"`) |
| other (including initial `null`) | `Installation Incomplete` (type `warning`) |
| key missing | `Installation Returned Malformed Response` (type `warning`) |

`execute()` returns `0` after `Installation Successful!`, whatever became of the optional steps, and `1` after every other callout.

`NewApplicationCommandTest` runs `execute()` whole. Four cases put a stand-in `composer` (or nothing) on a private `PATH`: composer missing, create-project succeeds, a PHP composer run under `PHP_BINARY`, create-project fails. The rest describe the machine with `FakeHost` and answer commands with `FakeProcessRunner`, and cover every path of the [Topside flow](/core/topside-flow.md). Extension-step paths: [ExtensionsFlow](/core/extensions-flow.md).[^command-test]

# Related

- [Node orchestration](/core/node-orchestration.md)
- [ExtensionsFlow](/core/extensions-flow.md)
- [ResolveDirectory](resolve-directory.md)
- [PrepareSharedBag](prepare-shared-bag.md)
- [Playbook: new application](/playbooks/new-application.md)

[^command]: NewApplicationCommand
[^flow-test]: Pest coverage for the nodes the command wires
[^resolve-test]: Pest coverage for the name → directory step
[^command-test]: Pest coverage for execute(), end to end

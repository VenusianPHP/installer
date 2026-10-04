---
type: Module
title: NewApplicationCommand
description: Symfony Console command `new` that scaffolds a Venusian app via PocketFlow.
resource: src/Console/Commands/NewApplicationCommand.php
tags: [component, command, new]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
verified: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
verification_key: "claude-fable-5-1@6689f865c8b40863d13dc520135c0217579d3224"
status: stable
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

It then [PrepareSharedBag](prepare-shared-bag.md), wires the [node flow](/core/node-orchestration.md), and `Flow::run($shared)`.[^command][^flow-test]

# Callout outcomes

After the flow, the command inspects `$shared['success']`:[^command]

| `$shared['success']` | Callout |
|----------------------|---------|
| `true` | `Installation Successful!` — "Application ready at {$directory}", then [SummarizeExtensions](summarize-extensions.md) (extension outcomes, or why the step ended) |
| `false` | `Installation Failed` (type `error`) — key/value list of `$shared['errors']` (fallback `"Unknown" => "No Message Provided"`) |
| other (including initial `null`) | `Installation Incomplete` (type `warning`) |
| key missing | `Installation Returned Malformed Response` (type `warn`) |

`execute()` returns `0` after every callout, failure included.

`NewApplicationCommandTest` runs `execute()` whole: `PATH` set to a private directory holding a stand-in `composer` script (or nothing), callouts read from `Prompt::fake()`. Three cases: composer missing, create-project succeeds, create-project fails. The runs are non-interactive, so the extension step ends at its offer; its paths are covered in [ExtensionsFlow](/core/extensions-flow.md).[^command-test]

The constructor takes optional `ComposerProjectCreator` and `Filesystem`; `execute()` uses neither (creation runs inside `ProjectCreationNode`).

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

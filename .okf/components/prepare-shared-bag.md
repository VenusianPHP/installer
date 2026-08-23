---
type: Module
title: PrepareSharedBag
description: Seeds the PocketFlow shared bag for `venusian new`.
resource: src/Actions/PrepareSharedBag.php
tags: [component, action, shared]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: bag
    resource: src/Actions/PrepareSharedBag.php
    title: PrepareSharedBag::run
  - id: bag-test
    resource: tests/Actions/PrepareSharedBagTest.php
    title: Pest coverage for keys and output_callback
---

# Behavior (tested)

`PrepareSharedBag::run(InputInterface $input, OutputInterface $output): array` returns:[^bag][^bag-test]

| Key | Value |
|-----|-------|
| `success` | `null` |
| `name` | `rtrim((string) $input->getArgument('name'), '/\\')` |
| `actions` | `[]` |
| `interactive` | `$input->isInteractive()` (bool) |
| `output_callback` | `function (string $type, string $buffer) use ($output): void` that `$output->write($buffer)` |

Tests drive the callback with `ArrayInput` + `BufferedOutput` and assert the buffer is written.

`actions` stays empty for the rest of the 0.8 reconstitution; see [known gaps](/known-gaps.md) and [node orchestration](/core/node-orchestration.md).

# Related

- [Node orchestration](/core/node-orchestration.md)
- [NewApplicationCommand](new-application-command.md)

[^bag]: PrepareSharedBag::run
[^bag-test]: Pest coverage for keys and output_callback

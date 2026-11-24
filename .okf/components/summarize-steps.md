---
type: Module
title: SummarizeSteps
description: Turns the bag keys of new's optional steps (PHP extensions, Topside, toolkit SDKs) into callout content for the success box.
resource: src/Actions/SummarizeSteps.php
tags: [component, action, extensions, topside]
generated: { by: "claude-code/claude-opus-5-5", at: "2026-11-24T00:00:00Z" }
status: draft
sources:
  - id: action
    resource: src/Actions/SummarizeSteps.php
    title: SummarizeSteps::run
  - id: action-test
    resource: tests/Actions/SummarizeStepsTest.php
    title: Pest coverage for each shape
---

# Behavior (tested)

`SummarizeSteps::run(array $shared): array` — content items for `callout()`, one block per step that ran, in this order.[^action][^action-test]

| Step | Result key → | Note key → | Neither |
|---|---|---|---|
| PHP extensions | `extension_results` → `['PHP extensions:', Element::keyValueList(...)]` | `extensions_note` → `"PHP extensions: {note}"` | nothing |
| Topside | `topside_result` → `"Topside: {result}"` | `topside_note` → `"Topside: {note}"` | nothing |
| Toolkit SDKs | `toolkit_results` → `['Toolkit SDKs:', Element::keyValueList(...)]` | `toolkits_note` → `"Toolkit SDKs: {note}"` | nothing |

[NewApplicationCommand](new-application-command.md) spreads the result into the `Installation Successful!` callout, after the "Application ready at" line ([ExtensionsFlow](/core/extensions-flow.md), [Topside flow](/core/topside-flow.md)).

[^action]: SummarizeSteps::run
[^action-test]: Pest coverage for each shape

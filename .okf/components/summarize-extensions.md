---
type: Module
title: SummarizeExtensions
description: Turns the extension step's bag keys into callout content for the success box.
resource: src/Actions/SummarizeExtensions.php
tags: [component, action, extensions]
generated: { by: "claude-fable-5-1", at: "2026-10-04T00:00:00Z" }
status: draft
sources:
  - id: action
    resource: src/Actions/SummarizeExtensions.php
    title: SummarizeExtensions::run
  - id: action-test
    resource: tests/Actions/SummarizeExtensionsTest.php
    title: Pest coverage for the three shapes
---

# Behavior (tested)

`SummarizeExtensions::run(array $shared): array` — content items for `callout()`.[^action][^action-test]

| Bag | Returns |
|---|---|
| `extension_results` set | `['PHP extensions:', Element::keyValueList($results)]` |
| else `extensions_note` set | `["PHP extensions: {$note}"]` |
| neither | `[]` |

[NewApplicationCommand](new-application-command.md) spreads the result into the `Installation Successful!` callout, after the "Application ready at" line. The two keys never coexist: a note means the flow stopped before installing ([ExtensionsFlow](/core/extensions-flow.md)).

[^action]: SummarizeExtensions::run
[^action-test]: Pest coverage for the three shapes

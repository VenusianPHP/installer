---
type: Module
title: ComposerProjectCreator
description: Builds and runs `composer create-project` for the Venusian skeleton with an injectable finder and process factory.
resource: src/ComposerProjectCreator.php
tags: [component, composer, create-project]
generated: { by: "agent:cursor-grok-4.6", at: "2026-08-23T17:05:00Z" }
status: draft
sources:
  - id: creator
    resource: src/ComposerProjectCreator.php
    title: ComposerProjectCreator
  - id: skeleton
    resource: src/Enums/SkeletonPackage.php
    title: SkeletonPackage::VENUSIAN
  - id: not-found
    resource: src/Exceptions/ComposerNotFoundException.php
    title: ComposerNotFoundException message
  - id: creator-test
    resource: tests/ComposerProjectCreatorTest.php
    title: Pest coverage for argv, missing composer, exit code, output callback
---

# Role

Standalone helper for `composer create-project`. The `new` flow currently duplicates the argv inside [ProjectCreationNode](/core/node-orchestration.md) and does not call this class. Tests inject `executableFinder` and `processFactory`.[^creator][^creator-test]

# buildCreateProjectCommand

Given `$composerBinary` and `$directory`, returns:[^creator][^creator-test][^skeleton]

```
[$composerBinary, 'create-project', 'venusian/venusian:^0.8.2', $directory, '--remove-vcs', '--prefer-dist']
```

The skeleton token is `SkeletonPackage::VENUSIAN->value` (`venusian/venusian:^0.8.2`), not `^0.8.0`.

# create

1. `findComposer()` → `$executableFinder->find('composer')`.
2. If the finder returns `null`, throws `ComposerNotFoundException`.[^not-found][^creator-test]
3. Builds the command, runs it via `processFactory` with no cwd, forwards `$outputCallback($type, $buffer)` when the callback is not null.
4. Returns `$process->getExitCode()`, or `1` when the exit code is `null`.[^creator][^creator-test]

# Related

- [Enums](enums.md)
- [ProcessRunner](process-runner.md)
- [Node orchestration](/core/node-orchestration.md)

[^creator]: ComposerProjectCreator
[^skeleton]: SkeletonPackage::VENUSIAN
[^not-found]: ComposerNotFoundException message
[^creator-test]: Pest coverage for argv, missing composer, exit code, output callback

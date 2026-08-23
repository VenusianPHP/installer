<?php

namespace Venusian\Installer;

use Closure;
use Symfony\Component\Process\Process;
use Venusian\Installer\Enums\SkeletonPackage;
use Symfony\Component\Process\ExecutableFinder;
use Venusian\Installer\Exceptions\ComposerNotFoundException;

class ComposerProjectCreator
{
    /**
     * @param callable(array<int, string>, ?string): Process|null $processFactory
     */
    public function __construct(
        private ?ExecutableFinder $executableFinder = null,
        private ?Closure $processFactory = null,
    ) {
        $this->executableFinder ??= new ExecutableFinder;
        $this->processFactory ??= static fn (array $command, ?string $cwd = null): Process => new Process($command, $cwd, null, null, null);
    }

    public function findComposer(): ?string
    {
        return $this->executableFinder->find('composer');
    }

    /**
     * @return list<string>
     */
    public function buildCreateProjectCommand(string $composerBinary, string $directory): array
    {
        return [
            $composerBinary,
            'create-project',
            SkeletonPackage::VENUSIAN->value,
            $directory,
            '--remove-vcs',
            '--prefer-dist',
        ];
    }

    /**
     * @param  callable(string, string): void|null  $outputCallback
     */
    public function create(string $directory, ?callable $outputCallback = null): int
    {
        $composer = $this->findComposer();

        if (is_null($composer)) {
            throw new ComposerNotFoundException;
        }

        $command = $this->buildCreateProjectCommand($composer, $directory);
        $process = ($this->processFactory)($command, null);

        $process->run(function (string $type, string $buffer) use ($outputCallback): void {
            if (! is_null($outputCallback)) {
                $outputCallback($type, $buffer);
            }
        });

        $exitCode = $process->getExitCode();

        return is_null($exitCode) ? 1 : $exitCode;
    }
}

<?php

namespace Venusian\Installer;

use Symfony\Component\Process\Process;

class ProcessRunner
{
    /**
     * @param  callable(array<int, string>, ?string): Process|null  $processFactory
     */
    public function __construct(
        private $processFactory = null,
    ) {
        $this->processFactory ??= static fn (array $command, ?string $cwd = null): Process => new Process($command, $cwd, null, null, null);
    }

    /**
     * @param  list<string>  $command
     * @param  callable(string, string): void|null  $outputCallback
     */
    public function run(
        array $command,
        ?string $cwd = null,
        ?callable $outputCallback = null,
        bool $inheritTty = false,
    ): int {
        $process = ($this->processFactory)($command, $cwd);

        if ($inheritTty && $this->tryEnableTty($process)) {
            $process->run();
        } else {
            $process->run(function (string $type, string $buffer) use ($outputCallback): void {
                if (! is_null($outputCallback)) {
                    $outputCallback($type, $buffer);
                }
            });
        }

        $exitCode = $process->getExitCode();

        return is_null($exitCode) ? 1 : $exitCode;
    }

    /**
     * @param  list<string>  $command
     */
    public function succeeds(array $command, ?string $cwd = null): bool
    {
        return $this->run($command, $cwd) === 0;
    }

    /**
     * @param  list<string>  $command
     */
    public function output(array $command, ?string $cwd = null): ?string
    {
        $process = ($this->processFactory)($command, $cwd);
        $process->run();

        $exitCode = $process->getExitCode();

        if (is_null($exitCode) || $exitCode !== 0) {
            return null;
        }

        return trim($process->getOutput());
    }

    /**
     * True when the current STDIN is a terminal and Symfony can attach a TTY
     * (needed so nested `sudo` can prompt for a password).
     */
    public function canInheritTty(): bool
    {
        return Process::isTtySupported()
            && defined('STDIN')
            && is_resource(STDIN)
            && @stream_isatty(STDIN);
    }

    private function tryEnableTty(Process $process): bool
    {
        if (! $this->canInheritTty()) {
            return false;
        }

        try {
            $process->setTty(true);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}

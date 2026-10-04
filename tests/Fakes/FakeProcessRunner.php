<?php

namespace Venusian\Installer\Tests\Fakes;

use Closure;
use Venusian\Installer\ProcessRunner;

/**
 * Runs nothing. Each command is recorded and answered by the closure with
 * [exit code, stdout].
 */
final class FakeProcessRunner extends ProcessRunner
{
    /** @var list<list<string>> */
    public array $commands = [];

    /** @var list<list<string>> Commands asked to run on the caller's terminal. */
    public array $on_terminal = [];

    /**
     * @param  Closure(list<string>): array{int, string}  $answer
     */
    public function __construct(private Closure $answer)
    {
        parent::__construct();
    }

    public function run(array $command, ?string $cwd = null, ?callable $outputCallback = null, bool $inheritTty = false): int
    {
        $this->commands[] = $command;
        if ($inheritTty) {
            $this->on_terminal[] = $command;
        }

        [$exit, $output] = ($this->answer)($command);
        if (! is_null($outputCallback) && $output !== '') {
            $outputCallback('out', $output);
        }

        return $exit;
    }

    public function output(array $command, ?string $cwd = null): ?string
    {
        $this->commands[] = $command;
        [$exit, $output] = ($this->answer)($command);

        return $exit === 0 ? trim($output) : null;
    }
}

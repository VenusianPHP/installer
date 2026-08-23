<?php

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Venusian\Installer\Actions\PrepareSharedBag;

function prepareSharedBagInput(string $name, bool $interactive): ArrayInput
{
    $input = new ArrayInput(
        ['name' => $name],
        new InputDefinition([
            new InputArgument('name', InputArgument::REQUIRED),
        ]),
    );
    $input->setInteractive($interactive);

    return $input;
}

it('seeds success as null, a trimmed name, empty actions, and interactive bool', function () {
    $input = prepareSharedBagInput('my-app/', true);
    $output = new BufferedOutput;
    $shared = PrepareSharedBag::run($input, $output);

    expect($shared['success'])->toBeNull()
        ->and($shared['name'])->toBe('my-app')
        ->and($shared['actions'])->toBe([])
        ->and($shared['interactive'])->toBeTrue()
        ->and($shared['output_callback'])->toBeCallable();
});

it('records a non-interactive input as interactive false', function () {
    $shared = PrepareSharedBag::run(
        prepareSharedBagInput('demo', false),
        new BufferedOutput,
    );

    expect($shared['interactive'])->toBeFalse();
});

it('writes process buffer to the output via output_callback', function () {
    $output = new BufferedOutput;
    $shared = PrepareSharedBag::run(
        prepareSharedBagInput('demo', true),
        $output,
    );

    ($shared['output_callback'])('out', 'creating project...');

    expect($output->fetch())->toBe('creating project...');
});

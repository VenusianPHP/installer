<?php

use Laravel\Prompts\Elements\Element;
use Venusian\Installer\Actions\SummarizeExtensions;

it('has nothing to say when the extension step never ran', function () {
    expect(SummarizeExtensions::run(['success' => false]))->toBe([]);
});

it('gives the reason the step ended without installing', function () {
    expect(SummarizeExtensions::run(['extensions_note' => 'Declined.']))->toBe([
        'PHP extensions: Declined.',
    ]);
});

it('lists each extension with its outcome', function () {
    $results = ['kqueue' => 'installed', 'pcurl' => 'failed (PIE exit code 2)'];

    expect(SummarizeExtensions::run(['extension_results' => $results]))->toEqual([
        'PHP extensions:',
        Element::keyValueList($results),
    ]);
});

<?php

use Laravel\Prompts\Elements\Element;
use Venusian\Installer\Actions\SummarizeSteps;

it('has nothing to say when no optional step ran', function () {
    expect(SummarizeSteps::run(['success' => false]))->toBe([]);
});

it('gives the reason each step ended without installing', function () {
    expect(SummarizeSteps::run([
        'extensions_note' => 'Declined.',
        'topside_note' => 'Not offered: this run is not interactive.',
    ]))->toBe([
        'PHP extensions: Declined.',
        'Topside: Not offered: this run is not interactive.',
    ]);
});

it('lists each extension and toolkit with its outcome, and what became of Topside', function () {
    $extensions = ['kqueue' => 'installed', 'multicurl' => 'failed (PIE exit code 2)'];
    $toolkits = ['qt' => 'installed'];

    expect(SummarizeSteps::run([
        'extension_results' => $extensions,
        'topside_result' => 'installed, config published',
        'toolkit_results' => $toolkits,
    ]))->toEqual([
        'PHP extensions:',
        Element::keyValueList($extensions),
        'Topside: installed, config published',
        'Toolkit SDKs:',
        Element::keyValueList($toolkits),
    ]);
});

it('says when no toolkit was selected', function () {
    expect(SummarizeSteps::run(['topside_result' => 'installed, config published', 'toolkits_note' => 'None selected.']))->toBe([
        'Topside: installed, config published',
        'Toolkit SDKs: None selected.',
    ]);
});

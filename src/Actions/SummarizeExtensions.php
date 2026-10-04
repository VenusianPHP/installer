<?php

namespace Venusian\Installer\Actions;

use Laravel\Prompts\Elements\Element;
use Laravel\Prompts\Elements\ElementContract;

class SummarizeExtensions
{
    /**
     * The extension step's outcome as callout content: each extension with
     * what became of it, or the reason the step ended early. Empty when it
     * never ran.
     *
     * @param  array<string, mixed>  $shared
     * @return list<string|ElementContract>
     */
    public static function run(array $shared): array
    {
        if (array_key_exists('extension_results', $shared)) {
            return ['PHP extensions:', Element::keyValueList($shared['extension_results'])];
        }

        if (array_key_exists('extensions_note', $shared)) {
            return ["PHP extensions: {$shared['extensions_note']}"];
        }

        return [];
    }
}

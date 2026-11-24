<?php

namespace Venusian\Installer\Actions;

use Laravel\Prompts\Elements\Element;
use Laravel\Prompts\Elements\ElementContract;

class SummarizeSteps
{
    /**
     * The optional steps of `new` as callout content: the PHP extensions,
     * Topside and the toolkit SDKs, each with what became of it or the reason
     * it ended early. A step that never ran adds nothing.
     *
     * @param  array<string, mixed>  $shared
     * @return list<string|ElementContract>
     */
    public static function run(array $shared): array
    {
        return [
            ...self::step($shared, 'PHP extensions', 'extension_results', 'extensions_note'),
            ...self::step($shared, 'Topside', 'topside_result', 'topside_note'),
            ...self::step($shared, 'Toolkit SDKs', 'toolkit_results', 'toolkits_note'),
        ];
    }

    /**
     * @return list<string|ElementContract>
     */
    private static function step(array $shared, string $label, string $result_key, string $note_key): array
    {
        if (array_key_exists($result_key, $shared)) {
            return is_array($shared[$result_key])
                ? ["{$label}:", Element::keyValueList($shared[$result_key])]
                : ["{$label}: {$shared[$result_key]}"];
        }

        if (array_key_exists($note_key, $shared)) {
            return ["{$label}: {$shared[$note_key]}"];
        }

        return [];
    }
}

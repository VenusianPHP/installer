<?php

namespace Venusian\Installer\Workflows\Extensions;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Host;
use function Laravel\Prompts\confirm;

/**
 * Works out which extensions of the catalog can be installed here, and
 * whether to go on. With one extension named in the bag (`extension_only`),
 * that one is the selection and nothing is asked.
 */
class ExtensionOfferNode extends Node
{
    /**
     * @param  list<FirstPartyExtension>  $catalog  The extensions on offer.
     * @param  bool  $ask  Ask before showing the list. False when running the command was the yes.
     */
    public function __construct(
        private Host $host = new Host,
        private ?array $catalog = null,
        private bool $ask = true,
    ) {
        parent::__construct();

        $this->catalog ??= FirstPartyExtension::offeredByNew();
    }

    /** @var array<string, string> What `pie install` takes for each installable extension, by enum value. */
    private array $tokens = [];

    public function prep(mixed &$shared): mixed
    {
        return [
            'only' => $shared['extension_only'] ?? null,
            'interactive' => $shared['interactive'] ?? false,
        ];
    }

    /**
     * @return array{states: array<string, ?string>, tokens: array<string, string>, note: ?string, selected: ?list<string>}
     */
    public function exec(mixed $prep_res): mixed
    {
        return [...$this->decide($prep_res), 'tokens' => $this->tokens];
    }

    /**
     * @return array{states: array<string, ?string>, note: ?string, selected: ?list<string>}
     */
    private function decide(array $prep_res): array
    {
        $names = implode(', ', array_map(fn (FirstPartyExtension $extension): string => $extension->extension(), $this->catalog));

        if(!is_null($prep_res['only'])) {
            $extension = FirstPartyExtension::named($prep_res['only']);

            if(is_null($extension) || !in_array($extension, $this->catalog, true)) {
                return ['states' => [], 'note' => "Unknown extension [{$prep_res['only']}]. Choose one of: {$names}.", 'selected' => null];
            }

            $reason = $this->state($extension);

            return is_null($reason)
                ? ['states' => [$extension->value => null], 'note' => null, 'selected' => [$extension->value]]
                : ['states' => [$extension->value => $reason], 'note' => "Nothing to install: {$extension->extension()} ({$reason}).", 'selected' => null];
        }

        $states = [];
        foreach ($this->catalog as $extension) {
            $states[$extension->value] = $this->state($extension);
        }

        if(!in_array(null, $states, true)) {
            $reasons = [];
            foreach ($states as $package => $reason) {
                $reasons[] = FirstPartyExtension::from($package)->extension()." ({$reason})";
            }

            return ['states' => $states, 'note' => 'Nothing to install: '.implode(', ', $reasons).'.', 'selected' => null];
        }

        if(!$prep_res['interactive']) {
            $note = $this->ask
                ? 'Not offered: this run is not interactive.'
                : "Name the extension to install on a non-interactive run: {$names}.";

            return ['states' => $states, 'note' => $note, 'selected' => null];
        }

        if($this->ask) {
            $accepted = confirm(
                label: 'Install first-party PHP extensions?',
                hint: 'Built by PIE for the PHP binary running this installer.',
            );

            if(!$accepted) {
                return ['states' => $states, 'note' => 'Declined.', 'selected' => null];
            }
        }

        return ['states' => $states, 'note' => null, 'selected' => null];
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        $shared['extension_states'] = $exec_res['states'];
        $shared['extension_tokens'] = $exec_res['tokens'];

        if(!is_null($exec_res['note'])) {
            $shared['extensions_note'] = $exec_res['note'];
            return null;
        }

        if(!is_null($exec_res['selected'])) {
            $shared['extensions_selected'] = $exec_res['selected'];
        }

        return 'find-php-config';
    }

    /**
     * Why the extension cannot be installed here, or null when it can: the
     * operating system, then whether this PHP already loads it, then what
     * Packagist lists on its line. A tagged release installs as the enum's
     * value; with none, the line's development branch (0.10.x-dev) installs
     * instead; with neither, the extension cannot be installed. Packagist is
     * asked only for an extension that got that far, and an unanswered
     * question leaves the value as the token: PIE asks again and reports
     * what it finds.
     */
    private function state(FirstPartyExtension $extension): ?string
    {
        $reason = $extension->unsupportedOn($this->host->osFamily())
            ?? ($this->host->loaded($extension->extension()) ? 'installed' : null);

        if(!is_null($reason)) {
            return $reason;
        }

        $this->tokens[$extension->value] = $extension->value;
        $releases = $this->host->releases($extension->package());

        if(is_null($releases)) {
            return null;
        }

        foreach ($releases as $version) {
            if(preg_match('/^'.preg_quote($extension->line(), '/').'\.\d+$/', $version) === 1) {
                return null;
            }
        }

        if(in_array("{$extension->line()}.x-dev", $releases, true)) {
            $this->tokens[$extension->value] = $extension->developmentToken();
            return null;
        }

        unset($this->tokens[$extension->value]);

        return "not on Packagist at {$extension->line()}";
    }
}

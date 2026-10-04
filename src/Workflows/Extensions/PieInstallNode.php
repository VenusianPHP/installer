<?php

namespace Venusian\Installer\Workflows\Extensions;

use ProjectSaturnStudios\PocketFlow\Node;
use Venusian\Installer\Enums\PiePackage;
use Venusian\Installer\Host;
use Venusian\Installer\ProcessRunner;
use function Laravel\Prompts\confirm;

class PieInstallNode extends Node
{
    public function __construct(
        private Host $host = new Host,
        private ProcessRunner $runner = new ProcessRunner,
    ) {
        parent::__construct();
    }

    public function prep(mixed &$shared): mixed
    {
        return [
            'interactive' => $shared['interactive'] ?? false,
            'output_callback' => $shared['output_callback'] ?? null,
        ];
    }

    /**
     * Downloads pie.phar into the home directory, then has it check its own
     * release attestation. A copy that fails the check is removed. Nothing is
     * downloaded without a yes, so a non-interactive run ends here.
     *
     * @return array{pie: ?string, note: ?string}
     */
    public function exec(mixed $prep_res): mixed
    {
        $home = $this->host->home();

        if(is_null($home)) {
            return ['pie' => null, 'note' => 'PIE is not installed, and there is no home directory to install it into.'];
        }

        if(!$prep_res['interactive']) {
            return ['pie' => null, 'note' => 'PIE is not installed. Run this command interactively to install it.'];
        }

        $url = PiePackage::PHAR_URL->value;
        $path = $home.'/'.PiePackage::INSTALL_DIRECTORY->value.'/'.PiePackage::BINARY->value;

        $accepted = confirm(
            label: 'PIE, the PHP extension installer, is not installed. Install it?',
            hint: 'Downloads pie.phar from github.com/php/pie to ~/'.PiePackage::INSTALL_DIRECTORY->value.'/'.PiePackage::BINARY->value.'.',
        );

        if(!$accepted) {
            return ['pie' => null, 'note' => 'PIE is not installed, and you declined to install it.'];
        }

        if(!$this->host->download($url, $path)) {
            return ['pie' => null, 'note' => "PIE could not be downloaded from {$url}."];
        }

        $verified = $this->runner->run(
            [$this->host->phpBinary(), $path, 'self-verify'],
            null,
            $prep_res['output_callback'],
        ) === 0;

        if(!$verified) {
            $this->host->remove($path);

            return ['pie' => null, 'note' => 'The downloaded PIE failed its own verification and was removed.'];
        }

        return ['pie' => $path, 'note' => null];
    }

    public function post(mixed &$shared, mixed $prep_res, mixed $exec_res): mixed
    {
        if(is_null($exec_res['pie'])) {
            $shared['extensions_note'] = $exec_res['note'];
            return null;
        }

        $shared['pie_binary'] = $exec_res['pie'];
        return 'select-extensions';
    }
}

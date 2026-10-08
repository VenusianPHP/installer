<?php

use Symfony\Component\Process\Process;
use Venusian\Installer\Enums\InstallerPackage;

it('reports the version composer.json declares from bin/venusian', function () {
    $root = dirname(__DIR__);
    $manifest = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $process = new Process(
        [PHP_BINARY, 'bin/venusian', '--version', '--no-interaction', '--no-ansi'],
        $root,
        [InstallerPackage::NO_UPDATE_CHECK_ENV->value => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and(trim($process->getOutput()))->toBe('Venusian PHP Application Installer '.$manifest['version']);
});

it('registers the new command in bin/venusian', function () {
    $process = new Process(
        [PHP_BINARY, 'bin/venusian', 'list', '--raw', '--no-interaction', '--no-ansi'],
        dirname(__DIR__),
        [InstallerPackage::NO_UPDATE_CHECK_ENV->value => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0)
        ->and($process->getOutput())->toMatch('/^new\s+Create a new Venusian PHP application$/m')
        ->and($process->getOutput())->toMatch('/^install:ext\s+Install Venusian\'s first-party PHP extensions through PIE$/m')
        ->and($process->getOutput())->toMatch('/^install:sdk\s+Install venusian\/build, which adds the build command$/m')
        ->and($process->getOutput())->not->toContain('probe-tool');
});

it('adds the commands of installed venusian-tool packages', function () {
    // A global-style install: <root>/vendor/autoload.php loads the real autoloader, then tells
    // Composer about one venusian-tool package whose composer.json declares a command class.
    $root = sys_get_temp_dir().'/venusian-global-'.bin2hex(random_bytes(4));
    mkdir($root.'/vendor/venusian/installer/bin', 0777, true);
    mkdir($root.'/vendor/acme/probe-tool', 0777, true);
    copy(dirname(__DIR__).'/bin/venusian', $root.'/vendor/venusian/installer/bin/venusian');
    file_put_contents($root.'/vendor/acme/probe-tool/composer.json', json_encode(['name' => 'acme/probe-tool', 'type' => 'venusian-tool', 'extra' => ['venusian' => ['commands' => ['Acme\\ProbeTool\\ProbeCommand']]]]));
    file_put_contents($root.'/vendor/acme/probe-tool/ProbeCommand.php', <<<'PHP'
    <?php
    namespace Acme\ProbeTool;
    use Symfony\Component\Console\Attribute\AsCommand;
    use Symfony\Component\Console\Command\Command;
    #[AsCommand(name: 'probe-tool', description: 'A command from a venusian-tool package')]
    class ProbeCommand extends Command {}
    PHP);
    file_put_contents($root.'/vendor/autoload.php', '<?php
    $loader = require '.var_export(dirname(__DIR__).'/vendor/autoload.php', true).';
    require '.var_export($root.'/vendor/acme/probe-tool/ProbeCommand.php', true).';
    Composer\InstalledVersions::reload([
        "root" => ["name" => "__root__", "version" => "dev-main", "pretty_version" => "dev-main", "reference" => null, "type" => "project", "install_path" => '.var_export($root, true).', "aliases" => [], "dev" => true],
        "versions" => [
            "acme/probe-tool" => ["version" => "1.0.0", "pretty_version" => "1.0.0", "reference" => null, "type" => "venusian-tool", "install_path" => '.var_export($root.'/vendor/acme/probe-tool', true).', "aliases" => [], "dev_requirement" => false],
        ],
    ]);
    return $loader;');

    $process = new Process(
        [PHP_BINARY, $root.'/vendor/venusian/installer/bin/venusian', 'list', '--raw', '--no-interaction', '--no-ansi'],
        $root,
        [InstallerPackage::NO_UPDATE_CHECK_ENV->value => '1'],
    );
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getOutput().$process->getErrorOutput())
        ->and($process->getOutput())->toMatch('/^probe-tool\s+A command from a venusian-tool package$/m');

    (new Symfony\Component\Filesystem\Filesystem)->remove($root);
});

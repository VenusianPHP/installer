<?php

use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Venusian\Installer\Console\Commands\NewApplicationCommand;

/*
 * `new` end to end, with PATH pointed at a directory that holds either a
 * stand-in `composer` (a shell script that only exits) or nothing. No
 * network, no real composer.
 */

beforeEach(function () {
    $this->previousPath = getenv('PATH');
    $this->bin = sys_get_temp_dir().'/venusian-installer-'.bin2hex(random_bytes(6));
    (new Filesystem)->mkdir($this->bin);
    putenv("PATH={$this->bin}");
    Prompt::fake();
});

afterEach(function () {
    putenv("PATH={$this->previousPath}");
    (new Filesystem)->remove($this->bin);
});

function standInComposer(string $bin, int $exit): void
{
    $filesystem = new Filesystem;
    $filesystem->dumpFile("{$bin}/composer", "#!/bin/sh\necho \"\$@\" >> \"{$bin}/composer.calls\"\nexit {$exit}\n");
    $filesystem->chmod("{$bin}/composer", 0755);
}

it('fails without running create-project when composer is not on PATH', function () {
    $tester = new CommandTester(new NewApplicationCommand);

    $exit = $tester->execute(['name' => "{$this->bin}/app"], ['interactive' => false]);

    expect($exit)->toBe(0)
        ->and(Prompt::strippedContent())->toContain('Installation Failed')
        ->and(Prompt::strippedContent())->toContain('Could not find the composer executable.');
});

it('creates the application and reports that extensions are not offered on a non-interactive run', function () {
    standInComposer($this->bin, 0);
    $tester = new CommandTester(new NewApplicationCommand);

    $exit = $tester->execute(['name' => "{$this->bin}/app"], ['interactive' => false]);

    expect($exit)->toBe(0)
        ->and(trim((string) file_get_contents("{$this->bin}/composer.calls")))->toBe("create-project venusian/venusian:^0.10.0 {$this->bin}/app --remove-vcs --prefer-dist")
        ->and(Prompt::strippedContent())->toContain('Installation Successful!')
        ->and(Prompt::strippedContent())->toContain('Application ready at')
        ->and(Prompt::strippedContent())->toMatch('/PHP extensions: (Not offered: this run is not interactive\.|Nothing to install: )/');
});

it('does not reach the extension step when create-project fails', function () {
    standInComposer($this->bin, 3);
    $tester = new CommandTester(new NewApplicationCommand);

    $exit = $tester->execute(['name' => "{$this->bin}/app"], ['interactive' => false]);

    expect($exit)->toBe(0)
        ->and(Prompt::strippedContent())->toContain('Installation Failed')
        ->and(Prompt::strippedContent())->not->toContain('PHP extensions');
});

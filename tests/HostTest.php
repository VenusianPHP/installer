<?php

use Symfony\Component\Filesystem\Filesystem;
use Venusian\Installer\Host;

beforeEach(function () {
    $this->directory = sys_get_temp_dir().'/venusian-installer-host-'.bin2hex(random_bytes(6));
    (new Filesystem)->mkdir($this->directory);
    $this->previousHome = getenv('HOME');
});

afterEach(function () {
    (new Filesystem)->remove($this->directory);
    $this->previousHome === false ? putenv('HOME') : putenv("HOME={$this->previousHome}");
});

it('reports the PHP binary running it, its operating system and its loaded extensions', function () {
    $host = new Host;

    expect($host->phpBinary())->toBe(PHP_BINARY)
        ->and($host->osFamily())->toBe(PHP_OS_FAMILY)
        ->and($host->loaded('json'))->toBeTrue()
        ->and($host->loaded('no-such-extension'))->toBeFalse();
});

it('reads the home directory from HOME, and has none when HOME is unset or empty', function () {
    putenv('HOME=/home/dev');
    expect((new Host)->home())->toBe('/home/dev');

    putenv('HOME=');
    expect((new Host)->home())->toBeNull();

    putenv('HOME');
    expect((new Host)->home())->toBeNull();
});

it('finds an executable in an extra directory', function () {
    $name = 'venusian-host-probe';
    (new Filesystem)->dumpFile("{$this->directory}/{$name}", "#!/bin/sh\n");
    (new Filesystem)->chmod("{$this->directory}/{$name}", 0755);

    expect((new Host)->find($name))->toBeNull()
        ->and((new Host)->find($name, [$this->directory]))->toBe("{$this->directory}/{$name}");
});

it('downloads into an executable file, creating the directory', function () {
    (new Filesystem)->dumpFile("{$this->directory}/source.phar", 'phar bytes');
    $target = "{$this->directory}/home/.local/bin/pie";

    $arrived = (new Host)->download("file://{$this->directory}/source.phar", $target);

    expect($arrived)->toBeTrue()
        ->and(file_get_contents($target))->toBe('phar bytes')
        ->and(is_executable($target))->toBeTrue();
});

it('answers false and leaves no file when the download does not arrive', function () {
    $target = "{$this->directory}/home/.local/bin/pie";

    $arrived = (new Host)->download("file://{$this->directory}/missing.phar", $target);

    expect($arrived)->toBeFalse()
        ->and(file_exists($target))->toBeFalse();
});

it('removes a file', function () {
    (new Filesystem)->dumpFile("{$this->directory}/pie", 'x');

    (new Host)->remove("{$this->directory}/pie");

    expect(file_exists("{$this->directory}/pie"))->toBeFalse();
});

it('runs a PHP script or phar under the PHP binary running it, and anything else as it is', function () {
    $filesystem = new Filesystem;
    $filesystem->dumpFile("{$this->directory}/composer", "#!/usr/bin/env php\n<?php\n");
    $filesystem->dumpFile("{$this->directory}/bare", "<?php\n");
    $filesystem->dumpFile("{$this->directory}/wrapper", "#!/bin/sh\nexec php \"$@\"\n");

    expect((new Host)->phpCommand("{$this->directory}/composer"))->toBe([PHP_BINARY, "{$this->directory}/composer"])
        ->and((new Host)->phpCommand("{$this->directory}/bare"))->toBe([PHP_BINARY, "{$this->directory}/bare"])
        ->and((new Host)->phpCommand('/somewhere/composer.phar'))->toBe([PHP_BINARY, '/somewhere/composer.phar'])
        ->and((new Host)->phpCommand("{$this->directory}/wrapper"))->toBe(["{$this->directory}/wrapper"])
        ->and((new Host)->phpCommand("{$this->directory}/missing"))->toBe(["{$this->directory}/missing"]);
});

it('describes the PHP running it the way its php-config does', function () {
    expect((new Host)->phpBindir())->toBe(PHP_BINDIR)
        ->and((new Host)->phpPrefix())->toBe(PHP_PREFIX)
        ->and((new Host)->phpVersionId())->toBe(PHP_VERSION_ID)
        ->and((new Host)->threadSafe())->toBe((bool) PHP_ZTS);
});

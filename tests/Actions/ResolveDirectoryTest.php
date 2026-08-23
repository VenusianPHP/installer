<?php

use Venusian\Installer\Actions\ResolveDirectory;

it('passes a dot through unchanged', function () {
    expect(ResolveDirectory::run('.'))->toBe('.');
});

it('passes an absolute path through unchanged', function () {
    $absolute = DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'venusian-app';

    expect(ResolveDirectory::run($absolute))->toBe($absolute);
});

it('joins a relative name onto the current working directory', function () {
    expect(ResolveDirectory::run('demo'))->toBe(getcwd().DIRECTORY_SEPARATOR.'demo');
});

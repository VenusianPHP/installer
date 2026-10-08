<?php

use Venusian\Installer\Tools\ToolCommands;

/*
 * Installed packages of type venusian-tool add their commands to venusian
 * through extra.venusian.commands.
 */
it('lists the command classes each tool package declares, in package order', function () {
    $classes = ToolCommands::fromPackages([
        'venusian/build' => ['venusian' => ['commands' => ['Venusian\Build\Console\BuildCommand']]],
        'acme/tool' => ['venusian' => ['commands' => ['Acme\One', 'Acme\Two']]],
        'acme/quiet' => [],
        'acme/odd' => ['venusian' => ['commands' => 'not-a-list']],
    ]);

    expect($classes)->toBe(['Venusian\Build\Console\BuildCommand', 'Acme\One', 'Acme\Two']);
});

it('reads extra.venusian.commands from each installed tool package', function () {
    $root = sys_get_temp_dir().'/venusian-tools-'.bin2hex(random_bytes(4));
    mkdir($root.'/vendor/acme/tool', 0777, true);
    file_put_contents($root.'/vendor/acme/tool/composer.json', json_encode(['name' => 'acme/tool', 'type' => 'venusian-tool', 'extra' => ['venusian' => ['commands' => ['Acme\Tool\Command']]]]));

    $classes = ToolCommands::fromInstalled(
        fn (string $type): array => $type === ToolCommands::TYPE ? ['acme/tool', 'acme/gone'] : [],
        fn (string $package): ?string => $package === 'acme/tool' ? $root.'/vendor/acme/tool' : null,
    );

    expect($classes)->toBe(['Acme\Tool\Command']);

    unlink($root.'/vendor/acme/tool/composer.json');
    rmdir($root.'/vendor/acme/tool');
    rmdir($root.'/vendor/acme');
    rmdir($root.'/vendor');
    rmdir($root);
});

<?php

use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Enums\PiePackage;
use Venusian\Installer\Enums\Toolkit;

it('pins each first-party extension to its 0.11 line on Packagist', function () {
    expect(array_map(fn (FirstPartyExtension $extension): string => $extension->value, FirstPartyExtension::cases()))->toBe([
        'php-io-extensions/epoll:^0.11',
        'php-io-extensions/kqueue:^0.11',
        'php-io-extensions/multicurl:^0.11',
        'php-io-extensions/appkit:^0.11',
        'php-io-extensions/gtk:^0.11',
        'php-io-extensions/qt:^0.11',
        'php-io-extensions/raven:^0.11',
    ]);
});

it('splits the catalog into the core extensions and the ones Topside\'s toolkits run on', function () {
    expect(FirstPartyExtension::core())->toBe([FirstPartyExtension::EPOLL, FirstPartyExtension::KQUEUE, FirstPartyExtension::MULTICURL])
        ->and(FirstPartyExtension::toolkits())->toBe([FirstPartyExtension::APPKIT, FirstPartyExtension::GTK, FirstPartyExtension::QT, FirstPartyExtension::RAVEN]);
});

it('splits a value into its package and release line, and finds a case by name', function () {
    expect(FirstPartyExtension::RAVEN->package())->toBe('php-io-extensions/raven')
        ->and(FirstPartyExtension::RAVEN->line())->toBe('0.11')
        ->and(FirstPartyExtension::RAVEN->developmentToken())->toBe('php-io-extensions/raven:0.11.x-dev')
        ->and(FirstPartyExtension::named('Qt'))->toBe(FirstPartyExtension::QT)
        ->and(FirstPartyExtension::named('qt', FirstPartyExtension::core()))->toBeNull()
        ->and(FirstPartyExtension::named('gd'))->toBeNull();
});

it('names each extension as PHP loads it', function () {
    expect(array_map(fn (FirstPartyExtension $extension): string => $extension->extension(), FirstPartyExtension::cases()))
        ->toBe(['epoll', 'kqueue', 'multicurl', 'appkit', 'gtk', 'qt', 'raven']);
});

it('says why an operating system cannot have an extension', function (FirstPartyExtension $extension, string $family, ?string $reason) {
    expect($extension->unsupportedOn($family))->toBe($reason);
})->with([
    'epoll on Linux' => [FirstPartyExtension::EPOLL, 'Linux', null],
    'epoll on macOS' => [FirstPartyExtension::EPOLL, 'Darwin', 'Linux only'],
    'epoll on Windows' => [FirstPartyExtension::EPOLL, 'Windows', 'Linux only'],
    'kqueue on macOS' => [FirstPartyExtension::KQUEUE, 'Darwin', null],
    'kqueue on Linux' => [FirstPartyExtension::KQUEUE, 'Linux', 'macOS only'],
    'kqueue on BSD' => [FirstPartyExtension::KQUEUE, 'BSD', 'macOS only'],
    'multicurl on Linux' => [FirstPartyExtension::MULTICURL, 'Linux', null],
    'multicurl on macOS' => [FirstPartyExtension::MULTICURL, 'Darwin', null],
    'multicurl on BSD' => [FirstPartyExtension::MULTICURL, 'BSD', null],
    'multicurl on Windows' => [FirstPartyExtension::MULTICURL, 'Windows', 'not on Windows'],
    'appkit on macOS' => [FirstPartyExtension::APPKIT, 'Darwin', null],
    'appkit on Linux' => [FirstPartyExtension::APPKIT, 'Linux', 'macOS only'],
    'gtk on Linux' => [FirstPartyExtension::GTK, 'Linux', null],
    'gtk on macOS' => [FirstPartyExtension::GTK, 'Darwin', null],
    'gtk on BSD' => [FirstPartyExtension::GTK, 'BSD', 'Linux and macOS only'],
    'qt on Windows' => [FirstPartyExtension::QT, 'Windows', 'Linux and macOS only'],
    'raven on Linux' => [FirstPartyExtension::RAVEN, 'Linux', null],
    'raven on macOS' => [FirstPartyExtension::RAVEN, 'Darwin', null],
    'raven on Windows' => [FirstPartyExtension::RAVEN, 'Windows', 'Linux and macOS only'],
]);

it('describes each extension in a few words', function () {
    foreach (FirstPartyExtension::cases() as $extension) {
        expect($extension->description())->not->toBe('');
    }
});

it('pins each toolkit adapter to its 0.11 line and names the extension it runs on', function () {
    expect(array_map(fn (Toolkit $toolkit): string => $toolkit->value, Toolkit::cases()))->toBe([
        'jovian/venusian-appkit:^0.11',
        'jovian/venusian-gtk:^0.11',
        'jovian/venusian-qt:^0.11',
        'jovian/venusian-raven:^0.11',
    ])
        ->and(Toolkit::QT->package())->toBe('jovian/venusian-qt')
        ->and(Toolkit::QT->key())->toBe('qt')
        ->and(array_map(fn (Toolkit $toolkit): FirstPartyExtension => $toolkit->extension(), Toolkit::cases()))->toBe(FirstPartyExtension::toolkits());
});

it('needs ext-appkit for GTK on macOS only', function () {
    expect(Toolkit::GTK->extensionsOn('Darwin'))->toBe([FirstPartyExtension::GTK, FirstPartyExtension::APPKIT])
        ->and(Toolkit::GTK->extensionsOn('Linux'))->toBe([FirstPartyExtension::GTK])
        ->and(Toolkit::QT->extensionsOn('Darwin'))->toBe([FirstPartyExtension::QT])
        ->and(Toolkit::APPKIT->unsupportedOn('Linux'))->toBe('macOS only')
        ->and(Toolkit::RAVEN->unsupportedOn('Darwin'))->toBeNull();
});

it('exposes every PiePackage case value', function () {
    expect(PiePackage::BINARY->value)->toBe('pie')
        ->and(PiePackage::PHAR_URL->value)->toBe('https://github.com/php/pie/releases/latest/download/pie.phar')
        ->and(PiePackage::INSTALL_DIRECTORY->value)->toBe('.local/bin')
        ->and(PiePackage::VERSION_MARKER->value)->toBe('(PIE)');
});

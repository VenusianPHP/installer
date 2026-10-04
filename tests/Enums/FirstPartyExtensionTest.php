<?php

use Venusian\Installer\Enums\FirstPartyExtension;
use Venusian\Installer\Enums\PiePackage;

it('pins each first-party extension to its 0.10 line on Packagist', function () {
    expect(FirstPartyExtension::EPOLL->value)->toBe('php-io-extensions/epoll:^0.10')
        ->and(FirstPartyExtension::KQUEUE->value)->toBe('php-io-extensions/kqueue:^0.10')
        ->and(FirstPartyExtension::PCURL->value)->toBe('php-io-extensions/pcurl:^0.10')
        ->and(array_map(fn (FirstPartyExtension $extension): string => $extension->value, FirstPartyExtension::cases()))->toBe([
            'php-io-extensions/epoll:^0.10',
            'php-io-extensions/kqueue:^0.10',
            'php-io-extensions/pcurl:^0.10',
            'php-io-extensions/appkit:^0.10',
            'php-io-extensions/gtk:^0.10',
            'php-io-extensions/qt:^0.10',
            'php-io-extensions/fb:^0.10',
            'php-io-extensions/rasterize:^0.10',
            'php-io-extensions/imgdec:^0.10',
            'php-io-extensions/posi:^0.10',
            'php-io-extensions/ftdi:^0.10',
        ]);
});

it('offers only epoll, kqueue and pcurl from new', function () {
    expect(FirstPartyExtension::offeredByNew())->toBe([FirstPartyExtension::EPOLL, FirstPartyExtension::KQUEUE, FirstPartyExtension::PCURL]);
});

it('splits a value into its package and release line, and finds a case by name', function () {
    expect(FirstPartyExtension::IMGDEC->package())->toBe('php-io-extensions/imgdec')
        ->and(FirstPartyExtension::IMGDEC->line())->toBe('0.10')
        ->and(FirstPartyExtension::named('Qt'))->toBe(FirstPartyExtension::QT)
        ->and(FirstPartyExtension::named('gd'))->toBeNull();
});

it('names each extension as PHP loads it', function () {
    expect(FirstPartyExtension::EPOLL->extension())->toBe('epoll')
        ->and(FirstPartyExtension::KQUEUE->extension())->toBe('kqueue')
        ->and(FirstPartyExtension::PCURL->extension())->toBe('pcurl');
});

it('says why an operating system cannot have an extension', function (FirstPartyExtension $extension, string $family, ?string $reason) {
    expect($extension->unsupportedOn($family))->toBe($reason);
})->with([
    'epoll on Linux' => [FirstPartyExtension::EPOLL, 'Linux', null],
    'epoll on macOS' => [FirstPartyExtension::EPOLL, 'Darwin', 'Linux only'],
    'epoll on BSD' => [FirstPartyExtension::EPOLL, 'BSD', 'Linux only'],
    'kqueue on macOS' => [FirstPartyExtension::KQUEUE, 'Darwin', null],
    'kqueue on Linux' => [FirstPartyExtension::KQUEUE, 'Linux', 'macOS only'],
    'kqueue on BSD' => [FirstPartyExtension::KQUEUE, 'BSD', 'macOS only'],
    'pcurl on Linux' => [FirstPartyExtension::PCURL, 'Linux', null],
    'pcurl on macOS' => [FirstPartyExtension::PCURL, 'Darwin', null],
    'pcurl on BSD' => [FirstPartyExtension::PCURL, 'BSD', null],
    'pcurl on Windows' => [FirstPartyExtension::PCURL, 'Windows', 'not on Windows'],
    'epoll on Windows' => [FirstPartyExtension::EPOLL, 'Windows', 'Linux only'],
    'appkit on macOS' => [FirstPartyExtension::APPKIT, 'Darwin', null],
    'appkit on Linux' => [FirstPartyExtension::APPKIT, 'Linux', 'macOS only'],
    'gtk on Linux' => [FirstPartyExtension::GTK, 'Linux', null],
    'gtk on macOS' => [FirstPartyExtension::GTK, 'Darwin', null],
    'gtk on BSD' => [FirstPartyExtension::GTK, 'BSD', 'Linux and macOS only'],
    'qt on Windows' => [FirstPartyExtension::QT, 'Windows', 'Linux and macOS only'],
    'fb on Linux' => [FirstPartyExtension::FB, 'Linux', null],
    'rasterize on BSD' => [FirstPartyExtension::RASTERIZE, 'BSD', 'Linux and macOS only'],
    'imgdec on macOS' => [FirstPartyExtension::IMGDEC, 'Darwin', null],
    'posi on BSD' => [FirstPartyExtension::POSI, 'BSD', null],
    'posi on Windows' => [FirstPartyExtension::POSI, 'Windows', 'not on Windows'],
    'ftdi on Linux' => [FirstPartyExtension::FTDI, 'Linux', null],
    'ftdi on Windows' => [FirstPartyExtension::FTDI, 'Windows', 'not on Windows'],
]);

it('describes each extension in a few words', function () {
    foreach (FirstPartyExtension::cases() as $extension) {
        expect($extension->description())->not->toBe('');
    }
});

it('exposes every PiePackage case value', function () {
    expect(PiePackage::BINARY->value)->toBe('pie')
        ->and(PiePackage::PHAR_URL->value)->toBe('https://github.com/php/pie/releases/latest/download/pie.phar')
        ->and(PiePackage::INSTALL_DIRECTORY->value)->toBe('.local/bin')
        ->and(PiePackage::VERSION_MARKER->value)->toBe('(PIE)');
});

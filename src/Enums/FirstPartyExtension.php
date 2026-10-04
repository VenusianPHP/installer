<?php

namespace Venusian\Installer\Enums;

/**
 * Venusian's first-party PHP extensions, installed through PIE. Each value is
 * the token `pie install` takes: the Packagist package and its version line.
 */
enum FirstPartyExtension: string
{
    case EPOLL = 'php-io-extensions/epoll:^0.10';
    case KQUEUE = 'php-io-extensions/kqueue:^0.10';
    case PCURL = 'php-io-extensions/pcurl:^0.10';
    case APPKIT = 'php-io-extensions/appkit:^0.10';
    case GTK = 'php-io-extensions/gtk:^0.10';
    case QT = 'php-io-extensions/qt:^0.10';
    case FB = 'php-io-extensions/fb:^0.10';
    case RASTERIZE = 'php-io-extensions/rasterize:^0.10';
    case IMGDEC = 'php-io-extensions/imgdec:^0.10';
    case POSI = 'php-io-extensions/posi:^0.10';
    case FTDI = 'php-io-extensions/ftdi:^0.10';

    /**
     * The ones `new` offers: what the framework itself picks up when loaded.
     *
     * @return list<self>
     */
    public static function offeredByNew(): array
    {
        return [self::EPOLL, self::KQUEUE, self::PCURL];
    }

    /** The case PHP loads under this name, or null. */
    public static function named(string $extension): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->extension() === strtolower($extension)) {
                return $case;
            }
        }

        return null;
    }

    /** The name PHP loads it under. */
    public function extension(): string
    {
        return strtolower($this->name);
    }

    /** The Packagist package, without the version line. */
    public function package(): string
    {
        return explode(':', $this->value)[0];
    }

    /** The release line the value pins: "0.10" for "^0.10". */
    public function line(): string
    {
        return ltrim(explode(':', $this->value)[1], '^');
    }

    /** The token for the line's development branch: "vendor/name:0.10.x-dev". */
    public function developmentToken(): string
    {
        return "{$this->package()}:{$this->line()}.x-dev";
    }

    public function description(): string
    {
        return match ($this) {
            self::EPOLL => 'event loop waiting on Linux',
            self::KQUEUE => 'event loop waiting on macOS',
            self::PCURL => 'HTTP requests that run on the event loop',
            self::APPKIT => 'native macOS windows and controls',
            self::GTK => 'GTK 4 windows and controls',
            self::QT => 'Qt 6 windows and controls',
            self::FB => 'framebuffers in C',
            self::RASTERIZE => 'shape and text rasterising in C',
            self::IMGDEC => 'PNG, JPEG and TIFF decoding in C',
            self::POSI => 'POSIX files, terminals and devices',
            self::FTDI => 'FTDI USB adapters: GPIO, I2C, SPI, UART',
        };
    }

    /**
     * Why this operating system cannot have the extension, or null when it can.
     *
     * @param  string  $os_family  A PHP_OS_FAMILY value.
     */
    public function unsupportedOn(string $os_family): ?string
    {
        return match ($this) {
            self::EPOLL => $os_family === 'Linux' ? null : 'Linux only',
            self::KQUEUE, self::APPKIT => $os_family === 'Darwin' ? null : 'macOS only',
            self::GTK, self::QT, self::FB, self::RASTERIZE, self::IMGDEC => in_array($os_family, ['Linux', 'Darwin'], true) ? null : 'Linux and macOS only',
            self::PCURL, self::POSI, self::FTDI => $os_family === 'Windows' ? 'not on Windows' : null,
        };
    }
}

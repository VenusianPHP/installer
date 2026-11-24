<?php

namespace Venusian\Installer\Enums;

/**
 * Venusian's first-party PHP extensions, installed through PIE. Each value is
 * the token `pie install` takes: the Packagist package and its version line.
 */
enum FirstPartyExtension: string
{
    case EPOLL = 'php-io-extensions/epoll:^0.11';
    case KQUEUE = 'php-io-extensions/kqueue:^0.11';
    case MULTICURL = 'php-io-extensions/multicurl:^0.11';
    case APPKIT = 'php-io-extensions/appkit:^0.11';
    case GTK = 'php-io-extensions/gtk:^0.11';
    case QT = 'php-io-extensions/qt:^0.11';
    case RAVEN = 'php-io-extensions/raven:^0.11';

    /**
     * The ones the framework itself picks up when loaded: what `new` and
     * `install:ext` offer.
     *
     * @return list<self>
     */
    public static function core(): array
    {
        return [self::EPOLL, self::KQUEUE, self::MULTICURL];
    }

    /**
     * The ones Topside's toolkits run on: what `install:toolkit-ext` offers.
     *
     * @return list<self>
     */
    public static function toolkits(): array
    {
        return [self::APPKIT, self::GTK, self::QT, self::RAVEN];
    }

    /**
     * The case of the catalog PHP loads under this name, or null.
     *
     * @param  ?list<self>  $catalog  Every case when null.
     */
    public static function named(string $extension, ?array $catalog = null): ?self
    {
        foreach ($catalog ?? self::cases() as $case) {
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

    /** The release line the value pins: "0.11" for "^0.11". */
    public function line(): string
    {
        return ltrim(explode(':', $this->value)[1], '^');
    }

    /** The token for the line's development branch: "vendor/name:0.11.x-dev". */
    public function developmentToken(): string
    {
        return "{$this->package()}:{$this->line()}.x-dev";
    }

    public function description(): string
    {
        return match ($this) {
            self::EPOLL => 'event loop waiting on Linux',
            self::KQUEUE => 'event loop waiting on macOS',
            self::MULTICURL => 'HTTP requests that run on the event loop',
            self::APPKIT => 'native macOS windows and controls',
            self::GTK => 'GTK 4 windows and controls',
            self::QT => 'Qt 6 windows and controls',
            self::RAVEN => 'Raven windows and controls',
        };
    }

    /**
     * Why this operating system cannot have the extension, or null when it
     * can. The families are the ones each package's composer.json names.
     *
     * @param  string  $os_family  A PHP_OS_FAMILY value.
     */
    public function unsupportedOn(string $os_family): ?string
    {
        return match ($this) {
            self::EPOLL => $os_family === 'Linux' ? null : 'Linux only',
            self::KQUEUE, self::APPKIT => $os_family === 'Darwin' ? null : 'macOS only',
            self::GTK, self::QT, self::RAVEN => in_array($os_family, ['Linux', 'Darwin'], true) ? null : 'Linux and macOS only',
            self::MULTICURL => $os_family === 'Windows' ? 'not on Windows' : null,
        };
    }
}

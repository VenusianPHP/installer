<?php

namespace Venusian\Installer\Enums;

/**
 * The toolkits Topside draws with. Each value is the `composer require` token
 * of its jovian adapter; each runs on a first-party PHP extension.
 */
enum Toolkit: string
{
    case APPKIT = 'jovian/venusian-appkit:^0.11';
    case GTK = 'jovian/venusian-gtk:^0.11';
    case QT = 'jovian/venusian-qt:^0.11';
    case RAVEN = 'jovian/venusian-raven:^0.11';

    /** The name it goes by: appkit, gtk, qt, raven. */
    public function key(): string
    {
        return strtolower($this->name);
    }

    /** The adapter package, without the version line. */
    public function package(): string
    {
        return explode(':', $this->value)[0];
    }

    /** The extension its adapter is written over. */
    public function extension(): FirstPartyExtension
    {
        return match ($this) {
            self::APPKIT => FirstPartyExtension::APPKIT,
            self::GTK => FirstPartyExtension::GTK,
            self::QT => FirstPartyExtension::QT,
            self::RAVEN => FirstPartyExtension::RAVEN,
        };
    }

    /**
     * Every extension the adapter needs loaded on this operating system. GTK
     * runs on NSApplication on macOS, and its adapter refuses to start there
     * without ext-appkit.
     *
     * @param  string  $os_family  A PHP_OS_FAMILY value.
     * @return list<FirstPartyExtension>
     */
    public function extensionsOn(string $os_family): array
    {
        return $this === self::GTK && $os_family === 'Darwin'
            ? [FirstPartyExtension::GTK, FirstPartyExtension::APPKIT]
            : [$this->extension()];
    }

    public function description(): string
    {
        return $this->extension()->description();
    }

    /**
     * Why this operating system cannot have the toolkit, or null when it can.
     *
     * @param  string  $os_family  A PHP_OS_FAMILY value.
     */
    public function unsupportedOn(string $os_family): ?string
    {
        return $this->extension()->unsupportedOn($os_family);
    }
}

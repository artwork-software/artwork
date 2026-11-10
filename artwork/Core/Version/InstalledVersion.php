<?php

namespace Artwork\Core\Version;

/**
 * Installierte artwork-Version aus der Datei VERSION im Projektverzeichnis.
 *
 * Die Nummer entspricht der Version im Changelog des Handbuchs und wird beim Release
 * in derselben Änderung hochgezählt, die nach main geht. Sie reist damit mit dem Code:
 * Eine Installation zeigt erst dann eine neue Version, wenn dieser Stand eingespielt ist.
 */
final class InstalledVersion
{
    /**
     * Erlaubt "1.8", "1.8.1" und Zusätze wie "1.9.0-dev" oder "1.8.1+hotfix".
     */
    private const string PATTERN = '/^\d+\.\d+(\.\d+)?([-+][0-9A-Za-z.-]+)?$/';

    /**
     * null, wenn die Datei fehlt, leer ist oder keine gültige Versionsnummer enthält.
     */
    public static function fromFile(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        return self::normalize((string) file_get_contents($path));
    }

    public static function normalize(string $rawVersion): ?string
    {
        $version = ltrim(trim($rawVersion), 'vV');

        return preg_match(self::PATTERN, $version) === 1 ? $version : null;
    }
}

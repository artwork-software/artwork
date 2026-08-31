<?php

namespace Artwork\Core\FileHandling;

/**
 * Effektives Upload-Limit der PHP-Serverkonfiguration (upload_max_filesize/post_max_size).
 * ACHTUNG: nginx (client_max_body_size) kann zusätzlich begrenzen, ist aus PHP aber nicht
 * auslesbar — Anzeigen dieses Werts daher immer mit entsprechendem Hinweis kombinieren
 * (Abnahme RG-04).
 */
final class ServerUploadLimit
{
    public static function inMegabytes(): int
    {
        return self::fromIniValues(ini_get('upload_max_filesize'), ini_get('post_max_size'));
    }

    /**
     * 0 bedeutet: kein bekanntes Limit (beide Direktiven unbegrenzt) — die Anzeigen
     * im Frontend blenden ihre Hinweise dann aus.
     */
    public static function fromIniValues(string|false $uploadMaxFilesize, string|false $postMaxSize): int
    {
        $limitBytes = min(
            self::iniToBytes($uploadMaxFilesize),
            self::iniToBytes($postMaxSize),
        );

        // Unbegrenzt: PHP_FLOAT_MAX darf nie zu int gecastet werden (E_WARNING
        // "not representable as an int" → ErrorException auf jedem Request).
        if ($limitBytes >= PHP_FLOAT_MAX) {
            return 0;
        }

        // Sub-1-MB-Limits nicht auf das falsy 0 abrunden — Meldungen zeigten sonst
        // "Server-Obergrenze von 0 MB" bzw. das Frontend hielte das Limit für unbekannt.
        return max(1, (int) floor($limitBytes / 1048576));
    }

    private static function iniToBytes(string|false $value): float
    {
        if ($value === false || trim($value) === '') {
            return PHP_FLOAT_MAX;
        }

        $value = trim($value);

        // "0" bzw. "-1" bedeuten in diesen Direktiven "unbegrenzt"
        if ($value === '0' || $value === '-1') {
            return PHP_FLOAT_MAX;
        }

        $number = (float) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }
}

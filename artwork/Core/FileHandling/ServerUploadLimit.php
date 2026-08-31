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
        return (int) floor(min(
            self::iniToBytes(ini_get('upload_max_filesize')),
            self::iniToBytes(ini_get('post_max_size')),
        ) / 1048576);
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

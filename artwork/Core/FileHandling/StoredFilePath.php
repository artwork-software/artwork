<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling;

/**
 * Normalisiert in der Datenbank gespeicherte Dateipfade auf die Form, mit der die Disks arbeiten
 * (relativ, ohne "/storage/"- oder "public/"-Präfix, ohne Host); Altbestand enthält solche Präfixe.
 */
final class StoredFilePath
{
    private function __construct()
    {
    }

    /**
     * "/storage/x/y", "storage/x/y", "public/x/y", "https://host/storage/x/y" → "x/y".
     * Traversal-Muster, Nullbytes und leere Pfade ergeben null.
     */
    public static function normalise(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $path = trim($raw);

        if ($path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^(https?://[^/]+)?/?(storage/|public/)?#i', '', $path) ?? $path;
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        return $path;
    }

    /**
     * @param list<string> $allowedDirectories
     */
    public static function normaliseWithin(mixed $raw, array $allowedDirectories): ?string
    {
        $path = self::normalise($raw);

        if ($path === null) {
            return null;
        }

        foreach ($allowedDirectories as $directory) {
            if (str_starts_with($path, rtrim($directory, '/') . '/')) {
                return $path;
            }
        }

        return null;
    }
}

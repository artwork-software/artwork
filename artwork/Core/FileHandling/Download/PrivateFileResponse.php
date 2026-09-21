<?php

declare(strict_types=1);

namespace Artwork\Core\FileHandling\Download;

use Artwork\Core\FileHandling\Naming\DownloadFileName;
use Artwork\Core\FileHandling\StoredFilePath;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liefert Dateien von der privaten "local"-Disk aus; was artwork:security:move-public-files noch nicht
 * verschoben hat, wird von "public" gelesen. Inline gibt es nur für Bilder und PDF, alles andere geht
 * als Attachment raus.
 */
final class PrivateFileResponse
{
    /**
     * @var list<string>
     */
    public const INLINE_MIME_TYPES = [
        'application/pdf',
        'image/avif',
        'image/bmp',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private function __construct()
    {
    }

    public static function make(string $path, ?string $downloadName = null, bool $inline = false): StreamedResponse
    {
        $path = StoredFilePath::normalise($path) ?? '';
        $disk = self::resolveDisk($path);

        abort_if($disk === null, 404);

        $name = DownloadFileName::sanitize($downloadName) ?? basename($path);

        if ($inline && self::canDisplayInline($disk, $path)) {
            return Storage::disk($disk)->response($path, $name);
        }

        return Storage::disk($disk)->download($path, $name);
    }

    /**
     * "local" hat Vorrang; "public" nur für noch nicht verschobene Dateien.
     */
    public static function resolveDisk(string $path): ?string
    {
        $path = StoredFilePath::normalise($path);

        if ($path === null) {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->fileExists($path)) {
                return $disk;
            }
        }

        return null;
    }

    private static function canDisplayInline(string $disk, string $path): bool
    {
        try {
            $mimeType = Storage::disk($disk)->mimeType($path);
        } catch (\Throwable) {
            return false;
        }

        return is_string($mimeType) && in_array(strtolower($mimeType), self::INLINE_MIME_TYPES, true);
    }
}

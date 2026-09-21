<?php

namespace Artwork\Modules\Inventory\Services;

use Artwork\Core\FileHandling\Naming\StoredFileName;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Throwable;

class InventoryArticleImageService
{
    private const STORAGE_DIR = 'inventory_articles';

    private const THUMBNAIL_DIR = 'inventory_articles/thumbnails';

    private const THUMBNAIL_MAX_DIMENSION = 400;

    private const THUMBNAIL_QUALITY = 80;

    private const HEIC_MIME_TYPES = ['image/heic', 'image/heif'];

    private const HEIC_EXTENSIONS = ['heic', 'heif'];

    /**
     * Obergrenze für die Dekodierung (Breite × Höhe). Die Validierung erlaubt bis 8192 px
     * je Kante (= 67 MP); mehr als 25 MP werden weder konvertiert noch verkleinert, damit
     * ein einzelnes Bild den Worker nicht in den Speicher-Limit treibt (Sicherheits-Audit 21.09.2026, F).
     */
    public const MAX_PIXELS = 25_000_000;

    /**
     * Store an uploaded article image as-is. Thumbnail generation AND the
     * HEIC→JPEG conversion (iPhone photos, browsers cannot render HEIC) run in
     * the queued job the repository dispatches after the database row exists -
     * decoding never happens inside the request (DoS-Schutz). Until the job
     * has run the frontend shows the placeholder logo (@error-Fallback).
     *
     * @return array{image: string, thumbnail: string|null}
     */
    public function store(UploadedFile $file): array
    {
        return [
            'image' => $file->storeAs(self::STORAGE_DIR, StoredFileName::forUpload($file), 'public'),
            'thumbnail' => null,
        ];
    }

    public function isHeic(string $imagePath): bool
    {
        if (Str::endsWith(strtolower($imagePath), array_map(static fn ($e) => '.' . $e, self::HEIC_EXTENSIONS))) {
            return true;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($imagePath)) {
            return false;
        }

        try {
            return in_array(strtolower((string) $disk->mimeType($imagePath)), self::HEIC_MIME_TYPES, true);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Generate a WebP thumbnail for an image that already exists on the
     * public disk. Returns null when the source is missing or cannot be
     * processed (e.g. SVG, corrupt file) — callers fall back to the original.
     */
    public function generateThumbnail(string $imagePath): ?string
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($imagePath) || Str::endsWith(strtolower($imagePath), '.svg')) {
            return null;
        }

        try {
            $this->assertPixelBudget($disk->path($imagePath));

            $image = $this->makeImageManager()->make($disk->path($imagePath));
            $image->resize(
                self::THUMBNAIL_MAX_DIMENSION,
                self::THUMBNAIL_MAX_DIMENSION,
                function ($constraint): void {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
            );

            // Hash suffix keeps legacy images from different folders with the
            // same basename from overwriting each other's thumbnail.
            $thumbnailPath = self::THUMBNAIL_DIR . '/'
                . pathinfo($imagePath, PATHINFO_FILENAME)
                . '_' . substr(md5($imagePath), 0, 8)
                . '.webp';

            $disk->put($thumbnailPath, (string) $image->encode('webp', self::THUMBNAIL_QUALITY));

            return $thumbnailPath;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Convert an existing HEIC/HEIF file on the public disk to JPEG and
     * return the new path. Requires Imagick with HEIC support; returns null
     * when conversion is not possible.
     */
    public function convertHeicToJpeg(string $imagePath): ?string
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($imagePath)) {
            return null;
        }

        try {
            $this->assertPixelBudget($disk->path($imagePath));

            $image = $this->makeImageManager('imagick')->make($disk->path($imagePath));
            $newPath = self::STORAGE_DIR . '/' . StoredFileName::forGenerated('jpg', $imagePath);
            $disk->put($newPath, (string) $image->encode('jpg', 90));

            return $newPath;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Header-only-Check (ping) vor dem Dekodieren: mehr als MAX_PIXELS werden nicht verarbeitet.
     */
    private function assertPixelBudget(string $absolutePath): void
    {
        $dimensions = null;

        try {
            // Bei ungültigen Bildern warnt getimagesize (Laravel macht daraus eine Exception → catch)
            $size = getimagesize($absolutePath);
            if ($size !== false) {
                $dimensions = [(int) $size[0], (int) $size[1]];
            }
        } catch (Throwable) {
            $dimensions = null;
        }

        if ($dimensions === null && class_exists(\Imagick::class)) {
            $ping = new \Imagick();
            self::applyImagickResourceLimits($ping);
            $ping->pingImage($absolutePath);
            $dimensions = [$ping->getImageWidth(), $ping->getImageHeight()];
            $ping->clear();
        }

        if ($dimensions !== null && ($dimensions[0] * $dimensions[1]) > self::MAX_PIXELS) {
            throw new \RuntimeException(sprintf(
                'Image exceeds the pixel budget (%d x %d > %d).',
                $dimensions[0],
                $dimensions[1],
                self::MAX_PIXELS
            ));
        }
    }

    /**
     * Imagick-Ressourcenlimits (gelten prozessweit): Speicher 256 MB, Map 512 MB, Disk 1 GB.
     * Die ImageMagick policy.xml des Containers ist nicht Teil des Repos - deshalb hier.
     */
    public static function applyImagickResourceLimits(?\Imagick $imagick = null): void
    {
        if (!class_exists(\Imagick::class)) {
            return;
        }

        try {
            $target = $imagick ?? new \Imagick();
            $target->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 256 * 1024 * 1024);
            $target->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 512 * 1024 * 1024);
            $target->setResourceLimit(\Imagick::RESOURCETYPE_DISK, 1024 * 1024 * 1024);
        } catch (Throwable) {
            // Limits sind Defence-in-Depth; ohne sie läuft die Verarbeitung wie bisher.
        }
    }

    private function makeImageManager(?string $driver = null): ImageManager
    {
        self::applyImagickResourceLimits();

        return new ImageManager([
            'driver' => $driver ?? (extension_loaded('imagick') ? 'imagick' : 'gd'),
        ]);
    }
}

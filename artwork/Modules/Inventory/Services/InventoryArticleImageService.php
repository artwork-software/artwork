<?php

namespace Artwork\Modules\Inventory\Services;

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

    /**
     * Store an uploaded article image. Thumbnail generation is dispatched by
     * the repository after the database row exists. HEIC/HEIF uploads (iPhone
     * photos) are converted to JPEG because browsers cannot render HEIC.
     *
     * @return array{image: string, thumbnail: string|null}
     */
    public function store(UploadedFile $file): array
    {
        if (in_array($file->getMimeType(), self::HEIC_MIME_TYPES, true)) {
            $path = $this->storeConvertedHeic($file);
        } else {
            $path = $file->store(self::STORAGE_DIR, 'public');
        }

        return [
            'image' => $path,
            'thumbnail' => null,
        ];
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
            $image = $this->makeImageManager('imagick')->make($disk->path($imagePath));
            $newPath = self::STORAGE_DIR . '/' . Str::random(40) . '.jpg';
            $disk->put($newPath, (string) $image->encode('jpg', 90));

            return $newPath;
        } catch (Throwable) {
            return null;
        }
    }

    private function storeConvertedHeic(UploadedFile $file): string
    {
        try {
            $image = $this->makeImageManager('imagick')->make($file->getRealPath());
            $path = self::STORAGE_DIR . '/' . Str::random(40) . '.jpg';
            Storage::disk('public')->put($path, (string) $image->encode('jpg', 90));

            return $path;
        } catch (Throwable) {
            // Without Imagick/HEIC support store the original — the frontend
            // falls back to the placeholder logo when it cannot render it.
            return $file->store(self::STORAGE_DIR, 'public');
        }
    }

    private function makeImageManager(?string $driver = null): ImageManager
    {
        return new ImageManager([
            'driver' => $driver ?? (extension_loaded('imagick') ? 'imagick' : 'gd'),
        ]);
    }
}

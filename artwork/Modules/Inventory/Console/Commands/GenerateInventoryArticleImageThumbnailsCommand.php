<?php

namespace Artwork\Modules\Inventory\Console\Commands;

use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateInventoryArticleImageThumbnailsCommand extends Command
{
    protected $signature = 'artwork:generate-inventory-article-thumbnails
        {--force : Regenerate thumbnails that already exist}';

    protected $description = 'Backfill WebP thumbnails for inventory article images and ' .
        'convert HEIC/HEIF originals to JPEG so browsers can display them';

    public function handle(InventoryArticleImageService $imageService): int
    {
        $disk = Storage::disk('public');
        $generated = 0;
        $converted = 0;
        $skipped = 0;
        $failed = 0;

        InventoryArticleImage::withTrashed()->chunkById(
            100,
            function ($images) use ($imageService, $disk, &$generated, &$converted, &$skipped, &$failed): void {
                foreach ($images as $image) {
                    if (!$image->image || !$disk->exists($image->image)) {
                        $skipped++;
                        continue;
                    }

                    if (Str::endsWith(strtolower($image->image), ['.heic', '.heif'])) {
                        $newPath = $imageService->convertHeicToJpeg($image->image);

                        if ($newPath === null) {
                            $this->warn("HEIC conversion failed: {$image->image} (image #{$image->id})");
                            $failed++;
                        } else {
                            $oldPath = $image->image;
                            $image->image = $newPath;
                            $converted++;

                            // Only remove the HEIC file when no other row still points to it.
                            $stillReferenced = InventoryArticleImage::withTrashed()
                                ->where('image', $oldPath)
                                ->where('id', '!=', $image->id)
                                ->exists();
                            if (!$stillReferenced) {
                                $disk->delete($oldPath);
                            }
                        }
                    }

                    if ($image->thumbnail && !$this->option('force') && $disk->exists($image->thumbnail)) {
                        $image->save();
                        continue;
                    }

                    $thumbnail = $imageService->generateThumbnail($image->image);
                    if ($thumbnail !== null) {
                        $image->thumbnail = $thumbnail;
                        $generated++;
                    }

                    $image->save();
                }
            }
        );

        $this->info("Thumbnails generated: {$generated}, HEIC converted: {$converted}, " .
            "skipped (missing file): {$skipped}, failed: {$failed}");

        return self::SUCCESS;
    }
}

<?php

namespace Artwork\Modules\Inventory\Console\Commands;

use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateInventoryArticleImageThumbnailsCommand extends Command
{
    protected $signature = 'artwork:generate-inventory-article-thumbnails
        {--force : Regenerate thumbnails that already exist}
        {--once : Run only once per environment (marker in one_time_tasks)}';

    private const string ONCE_KEY = 'inventory-article-images:generate-thumbnails';

    protected $description = 'Backfill WebP thumbnails for inventory article images and ' .
        'convert HEIC/HEIF originals to JPEG so browsers can display them';

    public function handle(InventoryArticleImageService $imageService): int
    {
        if ($this->option('once')) {
            $this->ensureOnceTableExists();

            if (DB::table('one_time_tasks')->where('key', self::ONCE_KEY)->exists()) {
                $this->info('Thumbnail backfill already completed (--once); skipping.');

                return self::SUCCESS;
            }
        }

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
                            continue;
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
                    if ($thumbnail === null) {
                        $this->warn("Thumbnail generation failed: {$image->image} (image #{$image->id})");
                        $failed++;
                        continue;
                    }

                    $image->thumbnail = $thumbnail;
                    $generated++;
                    $image->save();
                }
            }
        );

        $this->info("Thumbnails generated: {$generated}, HEIC converted: {$converted}, " .
            "skipped (missing file): {$skipped}, failed: {$failed}");

        if ($this->option('once') && $failed === 0) {
            DB::table('one_time_tasks')->updateOrInsert(
                ['key' => self::ONCE_KEY],
                ['executed_at' => now(), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function ensureOnceTableExists(): void
    {
        if (Schema::hasTable('one_time_tasks')) {
            return;
        }

        try {
            Schema::create('one_time_tasks', function (Blueprint $table): void {
                $table->id();
                $table->string('key')->unique();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();
            });
        } catch (\Throwable $exception) {
            if (!Schema::hasTable('one_time_tasks')) {
                throw $exception;
            }
        }
    }
}

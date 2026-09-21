<?php

namespace App\Jobs;

use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateInventoryArticleImageThumbnail implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public InventoryArticleImage $articleImage)
    {
    }

    public function handle(InventoryArticleImageService $imageService): void
    {
        // HEIC/HEIF (iPhone) wird hier - nicht im Request - nach JPEG konvertiert; das Original
        // wird ersetzt, sofern keine andere Zeile noch darauf zeigt.
        if ($this->articleImage->image && $imageService->isHeic($this->articleImage->image)) {
            $converted = $imageService->convertHeicToJpeg($this->articleImage->image);

            if ($converted !== null) {
                $oldPath = $this->articleImage->image;
                $this->articleImage->update(['image' => $converted, 'thumbnail' => null]);

                $stillReferenced = InventoryArticleImage::withTrashed()
                    ->where('image', $oldPath)
                    ->where('id', '!=', $this->articleImage->id)
                    ->exists();

                if (!$stillReferenced) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
        }

        if ($this->articleImage->thumbnail !== null) {
            return;
        }

        $thumbnail = $imageService->generateThumbnail($this->articleImage->image);

        if ($thumbnail !== null) {
            $this->articleImage->update(['thumbnail' => $thumbnail]);
        }
    }
}

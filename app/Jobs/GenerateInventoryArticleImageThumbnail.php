<?php

namespace App\Jobs;

use Artwork\Modules\Inventory\Models\InventoryArticleImage;
use Artwork\Modules\Inventory\Services\InventoryArticleImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateInventoryArticleImageThumbnail implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public InventoryArticleImage $articleImage)
    {
    }

    public function handle(InventoryArticleImageService $imageService): void
    {
        if ($this->articleImage->thumbnail !== null) {
            return;
        }

        $thumbnail = $imageService->generateThumbnail($this->articleImage->image);

        if ($thumbnail !== null) {
            $this->articleImage->update(['thumbnail' => $thumbnail]);
        }
    }
}

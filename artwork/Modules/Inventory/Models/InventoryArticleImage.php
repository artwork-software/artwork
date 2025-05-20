<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class InventoryArticleImage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'inventory_article_id',
        'image',
        'is_main_image',
        'order'
    ];

    protected $casts = [
        'is_main_image' => 'boolean'
    ];

    public function inventoryArticle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryArticle::class, 'inventory_article_id', 'id');
    }

    /**
     * Get the absolute URL for the image.
     * This method should only be used for API requests.
     *
     * @return string
     */
    public function getAbsoluteImageUrl(): string
    {
        if (!$this->image) {
            return '';
        }

        return url(Storage::url($this->image));
    }
}

<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryArticleImage extends Model
{
    use HasFactory;

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
}

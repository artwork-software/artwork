<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}

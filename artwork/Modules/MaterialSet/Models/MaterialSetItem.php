<?php

namespace Artwork\Modules\MaterialSet\Models;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialSetItem extends Model
{
    use HasFactory;

    protected $fillable = ['material_set_id', 'inventory_article_id', 'quantity'];
    protected $casts = [
        'quantity' => 'integer',
    ];

    protected $appends = ['name'];

    public function article(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            InventoryArticle::class,
            'inventory_article_id',
            'id',
            'articles'
        );
    }

    public function set(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            MaterialSet::class,
            'material_set_id',
            'id',
            'sets'
        );
    }

    public function getQuantityAttribute($value): int
    {
        return (int) $value;
    }

    public function getNameAttribute()
    {
        return $this->article ? $this->article->name : null;
    }
}

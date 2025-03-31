<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryPropertyValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_propertyable_type',
        'inventory_propertyable_id',
        'inventory_article_property_id',
        'value',
    ];

    /**
     * Definiert die morphTo Beziehung zu Artikeln, Kategorien oder Subkategorien.
     */
    public function inventoryPropertyable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Verknüpfung zur Property (z. B. Farbe, Material).
     */
    public function property(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryArticleProperties::class, 'inventory_article_property_id', 'id');
    }
}

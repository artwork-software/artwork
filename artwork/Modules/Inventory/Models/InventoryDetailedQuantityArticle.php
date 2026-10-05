<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\Traits\HasInventoryProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryDetailedQuantityArticle extends Model
{
    use HasFactory;
    use HasInventoryProperties;
    use SoftDeletes;

    protected $fillable = [
        'inventory_article_id',
        'name',
        'description',
        'quantity',
        'inventory_article_status_id',
        'external_id',
        'inventory_number',
        'detail_number',
    ];

    protected $casts = [
        'detail_number' => 'integer',
    ];



    protected $appends = ['room', 'manufacturer'];

    public function getRoomAttribute(): ?array
    {
        $roomProperty = $this->properties->firstWhere('type', 'room');

        if (!$roomProperty || !$roomProperty->pivot->value) {
            return null;
        }

        // gemeinsamer Cache mit InventoryArticle (dort auch gebündelt vorgeladen)
        $room = InventoryArticle::resolveRoom($roomProperty->pivot->value);

        if (!$room) {
            return null;
        }

        return [
            'id' => $room->id,
            'name' => $room->name,
            'property_id' => $roomProperty->id,
        ];
    }

    public function getManufacturerAttribute(): ?array
    {
        $manufacturerProperty = $this->properties->firstWhere('type', 'manufacturer');

        if (!$manufacturerProperty || !$manufacturerProperty->pivot->value) {
            return null;
        }

        $manufacturer = InventoryArticle::resolveManufacturer($manufacturerProperty->pivot->value);

        if (!$manufacturer) {
            return null;
        }

        return [
            'id' => $manufacturer->id,
            'name' => $manufacturer->display_name,
            'property_id' => $manufacturerProperty->id,
        ];
    }

    public function status()
    {
        return $this->belongsTo(InventoryArticleStatus::class, 'inventory_article_status_id')
            ->orderBy('order');
    }
}

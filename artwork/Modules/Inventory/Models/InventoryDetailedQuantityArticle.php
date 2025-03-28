<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\Traits\HasInventoryProperties;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryDetailedQuantityArticle extends Model
{
    use HasFactory;
    use HasInventoryProperties;

    protected $fillable = [
        'inventory_article_id',
        'name',
        'description',
        'quantity',
    ];

    protected $appends = ['room', 'manufacturer'];

    public function getRoomAttribute(): array
    {
        $roomProperty = $this->properties->firstWhere('type', 'room');

        if (!$roomProperty || !$roomProperty->pivot->value) {
            return [
                'name' => 'Room not found',
            ];
        }

        // Optimierung: Verwende Relation oder eager loading statt einzelner Query
        static $roomCache = [];

        $roomId = $roomProperty->pivot->value;

        if (!isset($roomCache[$roomId])) {
            $roomCache[$roomId] = Room::select('id', 'name')->find($roomId);
        }

        $room = $roomCache[$roomId];

        if (!$room) {
            return [
                'id' => $roomId,
                'name' => 'Room not found',
                'property_id' => $roomProperty->id,
            ];
        }

        return [
            'id' => $room->id,
            'name' => $room->name,
            'property_id' => $roomProperty->id,
        ];
    }

    public function getManufacturerAttribute(): array
    {
        $manufacturerProperty = $this->properties->firstWhere('type', 'manufacturer');

        if (!$manufacturerProperty || !$manufacturerProperty->pivot->value) {
            return [
                'name' => 'Manufacturer not found',
            ];
        }

        // Optimierung: Verwende Relation oder eager loading statt einzelner Query
        static $manufacturerCache = [];

        $manufacturerId = $manufacturerProperty->pivot->value;

        if (!isset($manufacturerCache[$manufacturerId])) {
            $manufacturerCache[$manufacturerId] = Manufacturer::select('id', 'name')->find($manufacturerId);
        }

        $manufacturer = $manufacturerCache[$manufacturerId];

        if (!$manufacturer) {
            return [
                'id' => $manufacturerId,
                'name' => 'Manufacturer not found',
                'property_id' => $manufacturerProperty->id,
            ];
        }

        return [
            'id' => $manufacturer->id,
            'name' => $manufacturer->name,
            'property_id' => $manufacturerProperty->id,
        ];
    }

}

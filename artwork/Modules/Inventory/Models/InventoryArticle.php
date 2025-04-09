<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Inventory\Models\Traits\HasInventoryProperties;
use Artwork\Modules\Manufacturer\Models\Manufacturer;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property string name
 * @property string description
 * @property int inventory_category_id
 * @property int inventory_sub_category_id
 * @property int quantity
 * @property bool is_detailed_quantity
 * @property \Illuminate\Database\Eloquent\Collection|\Artwork\Modules\Inventory\Models\InventoryArticleProperty[] properties
 * @property \Illuminate\Database\Eloquent\Collection|\Artwork\Modules\Inventory\Models\InventoryArticleImage[] images
 * @property \Artwork\Modules\Inventory\Models\InventoryCategory category
 * @property \Artwork\Modules\Inventory\Models\InventorySubCategory subCategory
 * @property int id
 * @property \Illuminate\Support\Carbon|null created_at
 * @property \Illuminate\Support\Carbon|null updated_at
 * @extends \Illuminate\Database\Eloquent\Model
 * @uses \Illuminate\Database\Eloquent\Factories\HasFactory
 * @uses \Artwork\Modules\Inventory\Models\InventoryArticleFactory
 */
class InventoryArticle extends Model
{
    use HasFactory;
    use HasInventoryProperties;
    use Searchable;
    use SoftDeletes;


    protected $fillable = [
        'name',
        'description',
        'inventory_category_id',
        'inventory_sub_category_id',
        'quantity',
        'is_detailed_quantity',
    ];

    protected $casts = [
        'is_detailed_quantity' => 'boolean',
        'quantity' => 'integer',
        'inventory_category_id' => 'integer',
        'inventory_sub_category_id' => 'integer',
        'created_at' => TranslatedDateTimeCast::class,
        'updated_at' => TranslatedDateTimeCast::class,
        'deleted_at' => TranslatedDateTimeCast::class,
    ];

    //protected $with = ['category', 'subCategory', 'properties', 'images'];

    protected $appends = ['room', 'manufacturer'];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id', 'id');
    }

    public function subCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventorySubCategory::class, 'inventory_sub_category_id', 'id');
    }

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryArticleImage::class, 'inventory_article_id', 'id');
    }

    public function detailedArticleQuantities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryDetailedQuantityArticle::class, 'inventory_article_id', 'id');
    }

    public function searchableAs(): string
    {
        return 'inventory_articles';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? 'Name not found',
            'description' => $this->description,
            'category' => $this?->category?->name ?? null,
            'sub_category' => $this?->subCategory?->name ?? null,
            'quantity' => $this->quantity ?? 0,
            'room' => $this?->room['name'] ?? null,
            'manufacturer' => $this?->manufacturer['name'] ?? null,
            'properties' => $this?->properties?->map(function ($property) {
                return [
                    'id' => $property->id,
                    'name' => $property->name,
                    'value' => $property->pivot->value
                ];
            })->toArray() ?? [],
        ];
    }

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

<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
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

    protected $appends = ['room', 'manufacturer', 'category', 'subCategory'];

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

    public function statusValues(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            InventoryArticleStatus::class,
            'inventory_article_status_values',
            'inventory_article_id',
            'inventory_article_status_id'
        )->withPivot('value')->orderBy('order');
    }

    public function internalIssues(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(InternalIssue::class, 'issuable', 'issuable_inventory_article')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function externalIssues(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(ExternalIssue::class, 'issuable', 'issuable_inventory_article')
            ->withPivot('quantity')
            ->withTimestamps();
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

    public function getCategoryAttribute()
    {
        return $this->getRelationValue('category');
    }

    public function getSubCategoryAttribute()
    {
        return $this->getRelationValue('subCategory');
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

    public function getAvailableStock(
        string $startDate,
        string $endDate,
        ?int $excludeIssueId = null,
        ?string $excludeType = null
    ): array {
        $sumQuantity = function ($issues): int {
            $used = 0;
            foreach ($issues as $issue) {
                $used += (int) ($issue->pivot->quantity ?? 0);
            }
            return $used;
        };

        if ($this->relationLoaded('internalIssues')) {
            $internalIssues = $this->internalIssues;
            // Apply exclusion filter to pre-loaded relations if needed
            if ($excludeType === 'intern' && $excludeIssueId) {
                $internalIssues = $internalIssues->filter(function ($issue) use ($excludeIssueId) {
                    return $issue->id !== $excludeIssueId;
                });
            }
        } else {
            $internalIssues = $this->internalIssues()
                ->where('start_date', '<=', $endDate)
                ->where(function ($q) use ($startDate) {
                    $q->where('end_date', '>=', $startDate)
                        ->orWhereNull('end_date');
                })
                ->when($excludeType === 'intern' && $excludeIssueId, function ($q) use ($excludeIssueId) {
                    $q->where('internal_issues.id', '!=', $excludeIssueId);
                })
                ->get();
        }

        if ($this->relationLoaded('externalIssues')) {
            $externalIssues = $this->externalIssues;
            // Apply exclusion filter to pre-loaded relations if needed
            if ($excludeType === 'extern' && $excludeIssueId) {
                $externalIssues = $externalIssues->filter(function ($issue) use ($excludeIssueId) {
                    return $issue->id !== $excludeIssueId;
                });
            }
        } else {
            $externalIssues = $this->externalIssues()
                ->where('issue_date', '<=', $endDate)
                ->where(function ($q) use ($startDate) {
                    $q->where('return_date', '>=', $startDate)
                        ->orWhereNull('return_date');
                })
                ->when($excludeType === 'extern' && $excludeIssueId, function ($q) use ($excludeIssueId) {
                    $q->where('external_issues.id', '!=', $excludeIssueId);
                })
                ->get();
        }

        $usedQuantity = $sumQuantity($internalIssues) + $sumQuantity($externalIssues);

        // Get the quantity of items with "Einsatzbereit" status
        $total = 0;

        if ($this->is_detailed_quantity) {
            // For detailed quantity articles, sum up the quantities of all detailed articles with "Einsatzbereit" status
            if ($this->relationLoaded('detailedArticleQuantities')) {
                $detailedQuantities = $this->detailedArticleQuantities;
            } else {
                // Load the detailedArticleQuantities relation if not already loaded
                $this->load('detailedArticleQuantities.status');
                $detailedQuantities = $this->detailedArticleQuantities;
            }

            foreach ($detailedQuantities as $detailedQuantity) {
                if ($detailedQuantity->status && $detailedQuantity->status->name === 'Einsatzbereit') {
                    $total += (int) $detailedQuantity->quantity;
                }
            }
        } else {
            // For regular articles, use the main article's status
            $readyStatus = null;
            if ($this->relationLoaded('statusValues')) {
                $readyStatus = $this->statusValues->firstWhere('name', 'Einsatzbereit');
            } else {
                // Load the statusValues relation if not already loaded
                $this->load('statusValues');
                $readyStatus = $this->statusValues->firstWhere('name', 'Einsatzbereit');
            }

            $total = $readyStatus ? (int) $readyStatus->pivot->value : 0;
        }
        $available = max($total - $usedQuantity, 0);

        return [
            'available' => $available,
            'total'     => $total,
            'reserved'  => $usedQuantity,
            'quantity'  => $total,
        ];
    }


}

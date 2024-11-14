<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $craft_inventory_category_id
 * @property string $name
 * @property int $order
 * @property Collection $items
 */
class CraftInventoryGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'craft_inventory_category_id',
        'name',
        'order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            CraftInventoryCategory::class,
            'craft_inventory_category_id',
            'id',
            'craft_inventory_categories'
        )->select(['id', 'craft_id', 'name', 'order']);
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            CraftInventoryItem::class,
            'craft_inventory_group_id',
            'id'
        )->select(['id', 'craft_inventory_group_id', 'order']);
    }
}

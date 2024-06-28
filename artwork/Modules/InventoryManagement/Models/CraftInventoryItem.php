<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $craft_inventory_group_id
 * @property int $order
 * @property CraftInventoryGroup $group
 * @property Collection $cells
 */
class CraftInventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'craft_inventory_group_id',
        'order',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(
            CraftInventoryGroup::class,
            'craft_inventory_group_id',
            'id',
            'craft_inventory_categories'
        )->select(['id', 'craft_inventory_category_id', 'name', 'order']);
    }

    public function cells(): HasMany
    {
        return $this->hasMany(
            CraftInventoryItemCell::class,
            'craft_inventory_item_id',
            'id'
        )
            ->orderBy('id')
            ->select(['id', 'crafts_inventory_column_id', 'craft_inventory_item_id', 'cell_value']);
    }
}

<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $craft_inventory_group_id
 * @property int $order
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
        );
    }

    public function cells(): HasMany
    {
        return $this->hasMany(CraftInventoryItemCell::class, 'craft_inventory_item_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CraftInventoryItemEvent::class, 'craft_inventory_item_id', 'id');
    }
}

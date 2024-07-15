<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $craft_id
 * @property string $name
 * @property int $order
 * @property Collection $groups
 */
class CraftInventoryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'craft_id',
        'name',
        'order',
    ];

    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class, 'craft_id', 'id', 'crafts');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(
            CraftInventoryGroup::class,
            'craft_inventory_category_id',
            'id'
        )
            ->orderBy('order')
            ->select(['id', 'craft_inventory_category_id', 'name', 'order']);
    }
}

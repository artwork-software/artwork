<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Artwork\Modules\Inventory\Enums\CraftsInventoryColumnTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int|CraftsInventoryColumnTypeEnum $type
 * @property array $type_options
 * @property string $background_color
 */
class CraftsInventoryColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'type_options',
        'background_color',
        'deletable',
        'order'
    ];

    protected $casts = [
        'type_options' => 'array',
        'deletable' => 'boolean'
    ];

    public function cells(): HasMany
    {
        return $this->hasMany(
            CraftInventoryItemCell::class,
            'crafts_inventory_column_id',
            'id'
        )->select(['id', 'crafts_inventory_column_id', 'craft_inventory_item_id', 'cell_value']);
    }
}

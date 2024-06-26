<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $craft_inventory_item_id
 * @property string $cell_value
 * @property array $cell_options
 */
class CraftInventoryItemCell extends Model
{
    use HasFactory;

    protected $fillable = [
        'crafts_inventory_column_id',
        'craft_inventory_item_id',
        'cell_value',
    ];

    protected $casts = [
        'cell_options' => 'array'
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            CraftInventoryItem::class,
            'craft_inventory_item_id',
            'id',
            'craft_inventory_items'
        );
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(
            CraftsInventoryColumn::class,
            'crafts_inventory_column_id',
            'id',
            'crafts_inventory_columns'
        );
    }
}

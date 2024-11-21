<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;

class CraftInventoryGroupFolder extends Model
{
    use HasFactory;


    protected $fillable = [
        'craft_inventory_group_id',
        'name',
        'order',
    ];

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            CraftInventoryGroup::class,
            'craft_inventory_group_id',
            'id',
            'craft_inventory_categories'
        )->select(['id', 'craft_inventory_category_id', 'name', 'order']);
    }


    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            CraftInventoryItem::class,
            'craft_inventory_group_folder_id',
            'id'
        )->select(['id', 'craft_inventory_group_folder_id', 'order']);
    }
}

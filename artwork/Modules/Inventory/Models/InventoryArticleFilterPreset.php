<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryArticleFilterPreset extends Model
{

    protected $fillable = [
        'user_id',
        'inventory_category_id',
        'inventory_sub_category_id',
        'name',
        'is_default',
        'filters',
        'tag_ids',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'filters' => 'array',
        'tag_ids' => 'array',
    ];
}

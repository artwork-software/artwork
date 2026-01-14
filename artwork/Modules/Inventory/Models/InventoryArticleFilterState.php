<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryArticleFilterState extends Model
{

    protected $fillable = [
        'user_id',
        'inventory_category_id',
        'inventory_sub_category_id',
        'filters',
        'tag_ids',
    ];

    protected $casts = [
        'filters' => 'array',
        'tag_ids' => 'array',
    ];
}

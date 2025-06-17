<?php

namespace Artwork\Modules\InternalIssue\Models;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SpecialItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'quantity', 'description', 'inventory_category_id', 'inventory_sub_category_id',
    ];

    public function issuable(): MorphTo
    {
        return $this->morphTo();
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class);
    }

    public function subCategory()
    {
        return $this->belongsTo(InventorySubCategory::class);
    }
}

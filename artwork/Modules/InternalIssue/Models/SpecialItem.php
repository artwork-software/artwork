<?php

namespace Artwork\Modules\InternalIssue\Models;

use Artwork\Modules\Inventory\Models\InventoryCategory;
use Artwork\Modules\Inventory\Models\InventorySubCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_issue_id',
        'name',
        'quantity',
        'description',
        'inventory_category_id',
        'inventory_sub_category_id',
    ];

    public function issue()
    {
        return $this->belongsTo(InternalIssue::class);
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(InventorySubCategory::class, 'inventory_sub_category_id');
    }
}

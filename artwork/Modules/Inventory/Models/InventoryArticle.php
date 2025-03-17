<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Modules\Inventory\Models\Traits\HasInventoryProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string name
 * @property string description
 * @property int inventory_category_id
 * @property int inventory_sub_category_id
 * @property int quantity
 * @extends \Illuminate\Database\Eloquent\Model
 * @uses \Illuminate\Database\Eloquent\Factories\HasFactory
 * @uses \Artwork\Modules\Inventory\Models\InventoryArticleFactory
 */
class InventoryArticle extends Model
{
    use HasFactory;
    use HasInventoryProperties;


    protected $fillable = [
        'name',
        'description',
        'inventory_category_id',
        'inventory_sub_category_id',
        'quantity',
        'is_detailed_quantity',
    ];

    protected $with = ['category', 'subCategory', 'properties'];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id', 'id');
    }

    public function subCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventorySubCategory::class, 'inventory_sub_category_id', 'id');
    }



}

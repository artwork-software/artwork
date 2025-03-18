<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\Inventory\Models\Traits\HasCategoryProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string name
 * @property string description
 * @property int order
 * @property array properties
 * @property int inventory_category_id
 * @property \Artwork\Modules\Inventory\Models\InventoryCategory category
 * @property \Illuminate\Database\Eloquent\Collection<\Artwork\Modules\Inventory\Models\InventoryArticle> articles
 * @extends \Illuminate\Database\Eloquent\Model
 * @uses \Illuminate\Database\Eloquent\Factories\HasFactory
 * @uses \Artwork\Modules\Inventory\Models\InventorySubCategoryFactory
 * @uses \Artwork\Modules\Inventory\Models\Traits\HasCategoryProperties
 * @uses \Artwork\Modules\Inventory\Models\InventoryArticle
 * @uses \Artwork\Modules\Inventory\Models\InventoryCategory
 * properties
 * @property
 */
class InventorySubCategory extends Model
{
    use HasFactory;
    use HasCategoryProperties;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => TranslatedDateTimeCast::class,
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id', 'id');
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryArticle::class, 'inventory_sub_category_id', 'id');
    }


}

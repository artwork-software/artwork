<?php

namespace Artwork\Modules\Inventory\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Inventory\Models\Traits\HasCategoryProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string name
 * @property string description
 * @property int order
 * @property array properties
 * @property \Illuminate\Database\Eloquent\Collection<\Artwork\Modules\Inventory\Models\InventorySubCategory> subCategories
 * @property \Illuminate\Database\Eloquent\Collection<\Artwork\Modules\Inventory\Models\InventoryArticle> articles
 * @extends \Illuminate\Database\Eloquent\Model
 */
class InventoryCategory extends Model
{
    use HasFactory;
    use HasCategoryProperties;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => TranslatedDateTimeCast::class,
    ];

    public function subCategories(): HasMany
    {
        return $this->hasMany(InventorySubCategory::class, 'inventory_category_id', 'id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(InventoryArticle::class, 'inventory_category_id', 'id');
    }

}

<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property array $category_ids
 * @property array $sub_category_ids
 * @property array $property_filters
 */
class InventoryUserFilter extends Model
{
    use HasFactory;

    protected $table = 'inventory_user_filters';

    protected $fillable = [
        'user_id',
        'category_ids',
        'sub_category_ids',
        'property_filters',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'sub_category_ids' => 'array',
        'property_filters' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\Artwork\Modules\User\Models\User::class, 'user_id');
    }
}

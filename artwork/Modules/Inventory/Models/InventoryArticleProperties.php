<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryArticleProperties extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_filterable',
        'show_in_list',
        'tooltip_text'
    ];

    /**
     * @var string[] $casts
     */
    protected $casts = [
        'is_filterable' => 'boolean',
        'show_in_list' => 'boolean',
    ];


    public function scopeFilterable($query) {
        return $query->where('is_filterable', true);
    }
}

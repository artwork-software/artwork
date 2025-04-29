<?php

namespace Artwork\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryArticleStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'default' => 'boolean',
        'deletable' => 'boolean',
    ];

}

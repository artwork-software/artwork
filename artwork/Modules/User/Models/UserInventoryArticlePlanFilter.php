<?php

namespace Artwork\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInventoryArticlePlanFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'only_planned',
        'open_categories',
        'open_subcategories',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'only_planned' => 'boolean',
        'open_categories' => 'array',
        'open_subcategories' => 'array',
    ];
}

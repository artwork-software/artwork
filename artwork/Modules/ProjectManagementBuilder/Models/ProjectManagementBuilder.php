<?php

namespace Artwork\Modules\ProjectManagementBuilder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectManagementBuilder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
        'is_active',
        'component',
        'deletable'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deletable' => 'boolean'
    ];
}

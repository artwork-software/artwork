<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectComponentValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'component_id',
        'project_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}

<?php

namespace Artwork\Modules\Permission\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermissionPreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array'
    ];
}

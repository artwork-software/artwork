<?php

namespace Artwork\Modules\ShiftQualification\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShiftQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'icon',
        'name',
        'available'
    ];

    protected $casts = [
        'available' => 'boolean'
    ];
}

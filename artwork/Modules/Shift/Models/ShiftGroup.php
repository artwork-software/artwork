<?php

namespace Artwork\Modules\Shift\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftGroup extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'color', 'icon'
    ];
}

<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainPositionVerified extends Model
{
    use HasFactory;
    use BelongsToMainPosition;

    protected $fillable = [
        'main_position_id',
        'requested_by',
        'requested'
    ];
}

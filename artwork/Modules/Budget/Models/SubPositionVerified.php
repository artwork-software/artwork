<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubPositionVerified extends Model
{
    use HasFactory;
    use BelongsToSubPosition;

    protected $fillable = [
        'sub_position_id',
        'requested_by',
        'requested'
    ];
}

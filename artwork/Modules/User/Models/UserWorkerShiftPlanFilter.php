<?php

namespace Artwork\Modules\User\Models;

use Artwork\Core\Database\Models\Model;

class UserWorkerShiftPlanFilter extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}

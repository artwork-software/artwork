<?php

namespace App\Models;

class MoneySourceUserPivot extends \Illuminate\Database\Eloquent\Relations\Pivot
{

    protected $casts = [
        'competent' => 'boolean',
        'write_access' => 'boolean'
    ];
}

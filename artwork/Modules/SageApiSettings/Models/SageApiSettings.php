<?php

namespace Artwork\Modules\SageApiSettings\Models;

use Artwork\Core\Database\Models\Model;

class SageApiSettings extends Model
{
    protected $fillable = [
        'host',
        'endpoint',
        'apiKey',
        'user',
        'password',
        'fetchTime',
        'enabled'
    ];

    protected $casts = [
        'enabled' => 'boolean'
    ];
}

<?php

namespace Artwork\Modules\EventProperty\Models;

use Artwork\Core\Database\Models\Model;

class EventProperty extends Model
{
    protected $fillable = [
        'icon',
        'name'
    ];
}

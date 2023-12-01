<?php

namespace Artwork\Modules\Vacation\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Vacation extends Model
{
    protected $table = 'vacations';

    protected $fillable = [
        'from',
        'to'
    ];

    public function vacations(): MorphTo
    {
        return $this->morphTo();
    }
}

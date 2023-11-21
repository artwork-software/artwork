<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainPositionVerified extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_position_id',
        'requested_by',
        'requested'
    ];

    public function main_position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MainPosition::class);
    }
}

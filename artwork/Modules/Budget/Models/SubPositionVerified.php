<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubPositionVerified extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_position_id',
        'requested_by',
        'requested'
    ];

    public function sub_position(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SubPosition::class);
    }
}

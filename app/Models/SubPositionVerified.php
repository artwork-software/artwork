<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sub_position_id
 * @property int $requested_by
 * @property int $requested
 * @property string $created_at
 * @property string $updated_at
 */
class SubPositionVerified extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_position_id',
        'requested_by',
        'requested'
    ];

    //@todo: fix phpcs error - refactor function name to subPosition
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function sub_position(): BelongsTo
    {
        return $this->belongsTo(SubPosition::class);
    }
}

<?php

namespace Artwork\Modules\Budget\Models;

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
    use BelongsToSubPosition;

    protected $fillable = [
        'sub_position_id',
        'requested_by',
        'requested'
    ];
}
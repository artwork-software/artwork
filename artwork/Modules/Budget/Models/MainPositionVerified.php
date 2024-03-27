<?php

namespace Artwork\Modules\Budget\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $main_position_id
 * @property int $requested_by
 * @property int $requested
 * @property string $created_at
 * @property string $updated_at
 */
class MainPositionVerified extends Model
{
    use HasFactory;
    use BelongsToMainPosition;
    use SoftDeletes;

    protected $fillable = [
        'main_position_id',
        'requested_by',
        'requested'
    ];
}

<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Modules\User\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;

/**
 * @property int $id
 * @property int $sub_position_row_id
 * @property int $user_id
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 */
class RowComment extends Model
{
    use HasFactory;
    use BelongsToUser;

    protected $fillable = [
        'sub_position_row_id',
        'user_id',
        'description'
    ];
}

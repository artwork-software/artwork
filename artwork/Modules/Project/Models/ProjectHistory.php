<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\User\Models\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 */
class ProjectHistory extends Model
{
    use HasFactory;
    use BelongsToUser;

    protected $guarded = [
        'id'
    ];
}

<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Project\Models\Traits\BelongsToProject;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $basename
 * @property int $project_id
 * @property string $deleted_at
 * @property string $created_at
 * @property string $updated_at
 */
class ProjectFile extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToProject;
    use SoftDeletes;

    protected $guarded = [
        'id',
    ];

    public function accessingUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}

<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 * @property bool $is_planning
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class ProjectState extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'is_planning'
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(
            Project::class,
            'state',
            'id'
        );
    }
}

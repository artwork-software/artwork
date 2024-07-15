<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $color
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class ProjectStates extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    protected $fillable = [
        'name',
        'color'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(
            Project::class,
            'state',
            'id',
            'projects'
        );
    }
}

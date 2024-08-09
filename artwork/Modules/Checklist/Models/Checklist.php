<?php

namespace Artwork\Modules\Checklist\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\Traits\BelongsToProject;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\Traits\BelongsToUser;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string $name
 * @property int $project_id
 * @property int $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|Task[] $tasks
 * @property-read Project $project
 * @property-read User $user
 * @property Collection|Department[]|array $departments
 */
class Checklist extends Model
{
    use BelongsToProject;
    use BelongsToUser;
    use HasFactory;
    use SoftDeletes;
    use Searchable;

    protected $fillable = [
        'name',
        'project_id',
        'user_id',
        'tab_id',
        'private',
        'creator_id',
    ];

    protected $casts = [
        'private' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'checklist_user',
            'checklist_id',
            'user_id'
        )->without([
            'calendar_settings', 'calendarAbo', 'shiftCalendarAbo'
        ]);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'id', 'users');
    }

    public function hasProject(): bool
    {
        return $this->project_id !== null;
    }

    public function searchableAs(): string
    {
        return 'checklists_index';
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('tasks');
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tasks' => $this->tasks->map(function (Task $task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'description' => $task->description,
                    'done' => $task->done,
                    'deadline' => $task->deadline,
                    'order' => $task->order,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                ];
            }),
        ];
    }
}

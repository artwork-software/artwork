<?php

namespace App\Models;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $project_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property \Illuminate\Database\Eloquent\Collection<Task> $tasks
 * @property Project $project
 * @property User $user
 * @property \Illuminate\Database\Eloquent\Collection<Department> $departments
 */
class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'project_id',
        'user_id'
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}

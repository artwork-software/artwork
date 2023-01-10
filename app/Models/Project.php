<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $number_of_participants
 * @property string $cost_center
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon $deleted_at
 *
 * @property \Illuminate\Database\Eloquent\Collection<User> $users
 * @property \Illuminate\Database\Eloquent\Collection<User> $adminUsers
 * @property \Illuminate\Database\Eloquent\Collection<User> $managerUsers
 * @property \Illuminate\Database\Eloquent\Collection<event> $events
 * @property \Illuminate\Database\Eloquent\Collection<Department> $departments
 * @property \Illuminate\Database\Eloquent\Collection<ProjectHistory> $project_histories
 * @property \Illuminate\Database\Eloquent\Collection<Checklist> $checklists
 * @property \Illuminate\Database\Eloquent\Collection<ProjectFile> $project_files
 * @property \Illuminate\Database\Eloquent\Collection<Comment> $comments
 * @property \Illuminate\Database\Eloquent\Collection<Category> $categories
 * @property \Illuminate\Database\Eloquent\Collection<Sector> $sectors
 * @property \Illuminate\Database\Eloquent\Collection<Genre> $genres
 * @property \Illuminate\Database\Eloquent\Collection<Project> $groups
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Room> $rooms
 * @property Sector $sector
 * @property Category $category
 * @property Genre $genre
 */
class Project extends Model
{
    use HasFactory, SoftDeletes, Prunable, Searchable, HasChangesHistory;

    protected $fillable = [
        'name',
        'description',
        'number_of_participants',
        'cost_center_id',
        'copyright_id',
    ];

    protected $with = ['historyChangesMorph'];

    public function cost_center()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function copyright()
    {
        return $this->belongsTo(Copyright::class, 'copyright_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->withPivot('is_admin', 'is_manager');
    }

    public function adminUsers()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('is_admin', true);
    }

    public function managerUsers()
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')
            ->wherePivot('is_manager', true);
    }

    public function events()
    {
        return $this->hasMany(event::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function project_histories()
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function project_files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function sectors()
    {
        return $this->belongsToMany(Sector::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'events');
    }

    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subMonth());
    }

    public function groups()
    {
        return $this->belongsToMany(Project::class, 'project_groups', 'group_id');
    }


    public function columns(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Column::class);
    }

    public function mainPositions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MainPosition::class);
    }


    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

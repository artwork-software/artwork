<?php

namespace App\Models;

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
 * @property int $sector_id
 * @property int $category_id
 * @property int $genre_id
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
 * @property Sector $sector
 * @property Category $category
 * @property Genre $genre
 */
class Project extends Model
{
    use HasFactory, SoftDeletes, Prunable, Searchable;

    protected $fillable = [
        'name',
        'description',
        'number_of_participants',
        'cost_center',
        'sector_id',
        'category_id',
        'genre_id'
    ];

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

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function genre()
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonth());
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

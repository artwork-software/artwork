<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    protected $fillable = [
        'name',
        'description',
        'number_of_participants',
        'cost_center',
        'sector_id',
        'category_id',
        'genre_id'
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'project_user', 'project_id')->withPivot('is_admin', 'is_manager');
    }

    public function departments() {
        return $this->belongsToMany(Department::class);
    }

    public function project_histories() {
        return $this->hasMany(ProjectHistory::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function project_files() {
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
}

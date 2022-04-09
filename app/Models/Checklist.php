<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}

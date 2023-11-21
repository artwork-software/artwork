<?php

namespace App\Models;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Genre extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    protected $fillable = [
        'name'
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}

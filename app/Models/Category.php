<?php

namespace App\Models;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int	$id
 * @property string	$name
 * @property \Carbon\Carbon	$created_at
 * @property \Carbon\Carbon	$updated_at
 *
 * @property \Illuminate\Support\Collection<\Artwork\Modules\Project\Models\Project> $projects
 */
class Category extends Model
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

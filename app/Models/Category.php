<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int	$id
 * @property string	$name
 * @property \Carbon\Carbon	$created_at
 * @property \Carbon\Carbon	$updated_at
 *
 * @property \Illuminate\Support\Collection<\App\Models\Project> $projects
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }
}

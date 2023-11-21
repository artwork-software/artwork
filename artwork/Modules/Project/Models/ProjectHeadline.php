<?php

namespace Artwork\Modules\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectHeadline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_project_headlines')->withPivot('text');
    }






}

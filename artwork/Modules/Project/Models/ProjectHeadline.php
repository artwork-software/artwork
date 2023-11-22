<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

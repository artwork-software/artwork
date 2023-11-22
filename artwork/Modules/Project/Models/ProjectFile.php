<?php

namespace Artwork\Modules\Project\Models;

use App\Models\Comment;
use App\Models\User;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectFile extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [
        'id'
    ];

    public function project() {
        return $this->belongsTo(Project::class, 'project_id' , 'id', 'projects');
    }

    public function accessing_users()
    {
        return $this->belongsToMany(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

}

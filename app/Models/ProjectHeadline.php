<?php

namespace App\Models;

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
        return $this->belongsToMany(Project::class)->withPivot('text');
    }
}

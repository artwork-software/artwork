<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'number_of_participants',
        'cost_center'
    ];

    public function users() {
        return $this->belongsToMany(User::class)->withPivot('is_admin');
    }

    public function departments() {
        return $this->belongsToMany(Department::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

}

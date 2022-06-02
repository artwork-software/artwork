<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'svg_name',
        'project_mandatory',
        'individual_name'
    ];

    public function events() {
        return $this->hasMany(event::class);
    }
}

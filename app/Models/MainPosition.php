<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'position'
    ];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function subPositions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubPosition::class);
    }

}

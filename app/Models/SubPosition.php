<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];
    public function mainPosition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MainPosition::class);
    }

    public function subPositionRows(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubPositionRow::class);
    }


}

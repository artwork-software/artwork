<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position'
    ];
    public function mainPosition(): BelongsTo
    {
        return $this->belongsTo(MainPosition::class);
    }

    public function subPositionRows(): HasMany
    {
        return $this->hasMany(SubPositionRow::class);
    }


}

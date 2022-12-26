<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubPositionRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'commented'
    ];

    public function subPosition(): BelongsTo
    {
        return $this->belongsTo(SubPosition::class);
    }

    public function columns(): BelongsToMany
    {
        return $this->belongsToMany(Column::class)
            ->withPivot(['value', 'linked_money_source_id','id', 'calculations'])
            ->withTimestamps();
    }

}

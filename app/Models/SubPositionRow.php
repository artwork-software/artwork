<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubPositionRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'commented'
    ];

    public function columns(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Column::class)->withPivot(['value']);
    }

}

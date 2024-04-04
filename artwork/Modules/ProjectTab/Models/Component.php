<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'data',
        'special'
    ];

    protected $casts = [
        'data' => 'array',
        'special' => 'boolean'
    ];


    public function projectValue(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectComponentValue::class, 'component_id', 'id');
    }

    public function scopeNotSpecial($query): Builder
    {
        return $query->where('special', false);
    }

    public function scopeIsSpecial($query): Builder
    {
        return $query->where('special', true);
    }
}

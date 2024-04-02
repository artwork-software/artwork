<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];


    public function projectValue(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectComponentValue::class, 'component_id', 'id');
    }
}

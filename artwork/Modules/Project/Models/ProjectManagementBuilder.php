<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectManagementBuilder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
        'is_active',
        'type',
        'deletable',
        'component_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deletable' => 'boolean'
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}

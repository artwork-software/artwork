<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentInTab extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_tab_id',
        'component_id',
        'order',
        'value'
    ];

    protected $with = ['component'];

    public function projectValue(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProjectComponentValue::class, 'component_in_tab_id', 'id');
    }

    public function component(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}

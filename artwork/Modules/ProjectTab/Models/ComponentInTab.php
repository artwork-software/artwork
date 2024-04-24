<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
* Class ComponentInTab
 * @package Artwork\Modules\ProjectTab\Models
 * @property Component component
 * @property int id
 * @property int project_tab_id
 * @property int component_id
 * @property int order
 * @property array scope
 */
class ComponentInTab extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_tab_id',
        'component_id',
        'order',
        'scope'
    ];

    protected $casts = [
        'scope' => 'array'
    ];

    protected $with = ['component'];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}

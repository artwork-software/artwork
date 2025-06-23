<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'scope',
        'note'
    ];

    protected $casts = [
        'scope' => 'array'
    ];

    protected $with = ['component', 'disclosureComponents'];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }


    public function disclosureComponents(): HasMany
    {
        return $this->hasMany(DisclosureComponents::class, 'disclosure_id', 'component_id')
            ->orderBy('order');
    }
}

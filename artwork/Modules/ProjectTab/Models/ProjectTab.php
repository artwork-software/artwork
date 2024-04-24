<?php

namespace Artwork\Modules\ProjectTab\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ProjectTab
 * @package Artwork\Modules\ProjectTab\Models
 * @property int $id
 * @property string $name
 * @property int $order
 * @property Collection<ComponentInTab> $components
 * @property Collection<ProjectTabSidebarTab> $sidebarTabs
 */
class ProjectTab extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'order',
    ];

    protected $appends = ['hasSidebarTabs'];

    protected $with = ['components', 'sidebarTabs'];

    public function components(): HasMany
    {
        return $this->hasMany(ComponentInTab::class, 'project_tab_id', 'id');
    }

    public function sidebarTabs(): HasMany
    {
        return $this->hasMany(ProjectTabSidebarTab::class, 'project_tab_id', 'id')->orderBy('order');
    }

    public function getHasSidebarTabsAttribute(): bool
    {
        return $this->sidebarTabs->isNotEmpty();
    }

    public function scopeByComponentsComponentType(Builder $builder, string $type): Builder
    {
        return $builder->whereRelation('components.component', 'type', $type);
    }
}

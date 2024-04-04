<?php

namespace Artwork\Modules\ProjectTab\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class ProjectTab
 * @package Artwork\Modules\ProjectTab\Models
 * @property ComponentInTab[] components
 * @property int id
 * @property string name
 * @property int order
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

    public function components(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ComponentInTab::class, 'project_tab_id', 'id');
    }

    public function sidebarTabs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectTabSidebarTab::class, 'project_tab_id', 'id')->orderBy('order');
    }

    public function getHasSidebarTabsAttribute(): bool
    {
        return $this->sidebarTabs->isNotEmpty();
    }
}

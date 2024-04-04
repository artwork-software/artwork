<?php

namespace Artwork\Modules\ProjectTab\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTabSidebarTab extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_tab_id',
        'name',
        'order',
    ];

    protected $with = ['componentsInSidebar'];

    public function projectTab(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProjectTab::class, 'project_tab_id', 'id', 'project_tab');
    }

    public function componentsInSidebar(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SidebarTabComponent::class, 'project_tab_sidebar_id', 'id')->orderBy('order');
    }
}

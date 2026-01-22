<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    protected $fillable = [
        'name',
        'order',
        'default',
        'visible_for_all',
    ];

    protected $casts = [
        'default' => 'boolean',
        'visible_for_all' => 'boolean',
    ];

    protected $appends = ['hasSidebarTabs'];

    protected $with = ['components', 'sidebarTabs'];

    public function visibleUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'project_tab_visible_users',
            'project_tab_id',
            'user_id'
        );
    }

    public function visibleDepartments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'project_tab_visible_departments',
            'project_tab_id',
            'department_id'
        );
    }

    public function visibleForUser(?User $user): bool
    {
        if($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return true;
        }
        if ($this->visible_for_all) {
            return true;
        }
        if (!$user) {
            return false;
        }

        if ($this->relationLoaded('visibleUsers')) {
            if ($this->visibleUsers->contains('id', $user->id)) {
                return true;
            }
        } else {
            if ($this->visibleUsers()->whereKey($user->id)->exists()) {
                return true;
            }
        }

        $userDepartmentIds = $user->departments()->pluck('departments.id');

        if ($this->relationLoaded('visibleDepartments')) {
            return $this->visibleDepartments->pluck('id')->intersect($userDepartmentIds)->isNotEmpty();
        }

        return $this->visibleDepartments()->whereIn('departments.id', $userDepartmentIds)->exists();
    }

    public function scopeVisibleForUser(Builder $q, User $user): Builder
    {
        if ($user->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return $q;
        }

        return $q->where(function (Builder $query) use ($user) {
            $query->where('visible_for_all', true)
                ->orWhereHas('visibleUsers', fn (Builder $uq) => $uq->where('users.id', $user->id))
                ->orWhereHas('visibleDepartments.users', fn (Builder $dq) => $dq->where('users.id', $user->id));
        });
    }


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

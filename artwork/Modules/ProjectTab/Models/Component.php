<?php

namespace Artwork\Modules\ProjectTab\Models;

use App\Models\User;
use Artwork\Modules\Department\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $name
 * @property string $permission_type
 * @property Collection<User> $users
 */
class Component extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'data',
        'special',
        'sidebar_enabled',
        'permission_type'
    ];

    protected $casts = [
        'data' => 'array',
        'special' => 'boolean',
        'sidebar_enabled' => 'boolean'
    ];

    protected $with = [
        'users',
        'departments'
    ];

    public function projectValue(): HasOne
    {
        return $this->hasOne(ProjectComponentValue::class, 'component_id', 'id');
    }

    public function sidebarTabComponent(): HasMany
    {
        return $this->hasMany(SidebarTabComponent::class, 'component_id', 'id');
    }

    public function tabComponent(): HasMany
    {
        return $this->hasMany(ComponentInTab::class, 'component_id', 'id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ComponentUser::class)
            ->withPivot(['can_write'])
            ->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->using(ComponentDepartment::class)
            ->withPivot(['can_write'])
            ->withTimestamps()
            ->with(['users']);
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

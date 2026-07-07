<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Project\Models\ComponentDepartment;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ComponentUser;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Component Model
 *
 * Represents a component in the project tab module.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property array $data
 * @property bool $special
 * @property bool $sidebar_enabled
 * @property string $permission_type
 * @property bool $is_bi_field
 * @property int|null $bi_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\Artwork\Modules\User\Models\User[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\Artwork\Modules\Department\Models\Department[] $departments
 * @property-read \Artwork\Modules\Project\Models\ProjectComponentValue $projectValue
 * @property-read \Illuminate\Database\Eloquent\Collection|\Artwork\Modules\Project\Models\SidebarTabComponent[] $sidebarTabComponent
 * @property-read \Illuminate\Database\Eloquent\Collection|\Artwork\Modules\Project\Models\ComponentInTab[] $tabComponent
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Component notSpecial()
 * @method static \Illuminate\Database\Eloquent\Builder|Component isSpecial()
 * @method static \Illuminate\Database\Eloquent\Builder|Component isBiField()
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
        'permission_type',
        'is_bi_field',
        'bi_order',
    ];


    protected $casts = [
        'data' => 'array',
        'special' => 'boolean',
        'sidebar_enabled' => 'boolean',
        'is_bi_field' => 'boolean',
    ];


    protected $with = [];

    /**
     * Get the project value associated with the component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function projectValue(): HasOne
    {
        return $this->hasOne(ProjectComponentValue::class, 'component_id', 'id');
    }

    /**
     * Get the sidebar tab components associated with the component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sidebarTabComponent(): HasMany
    {
        return $this->hasMany(SidebarTabComponent::class, 'component_id', 'id');
    }

    /**
     * Get the tab components associated with the component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tabComponent(): HasMany
    {
        return $this->hasMany(ComponentInTab::class, 'component_id', 'id');
    }

    /**
     * The users that belong to the component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ComponentUser::class)
            ->withPivot(['can_write'])
            ->withTimestamps();
    }

    /**
     * The departments that belong to the component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->using(ComponentDepartment::class)
            ->withPivot(['can_write'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include non-special components.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotSpecial(\Illuminate\Database\Eloquent\Builder $query): Builder
    {
        return $query->where('special', false);
    }

    /**
     * Scope a query to only include special components.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsSpecial(\Illuminate\Database\Eloquent\Builder $query): Builder
    {
        return $query->where('special', true);
    }

    /**
     * Scope a query to only include BI field components.
     */
    public function scopeIsBiField(\Illuminate\Database\Eloquent\Builder $query): Builder
    {
        return $query->where('is_bi_field', true);
    }

    public function componentInPrintLayouts(): HasMany
    {
        return $this->hasMany(PrintLayoutComponents::class, 'component_id', 'id');
    }

    public function componentInDisclosures(): HasMany
    {
        return $this->hasMany(DisclosureComponents::class, 'component_id', 'id');
    }
}

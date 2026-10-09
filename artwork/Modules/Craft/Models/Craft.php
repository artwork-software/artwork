<?php

namespace Artwork\Modules\Craft\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property bool $assignable_by_all
 * @property string $created_at
 * @property string $updated_at
 * @property string $color
 * @property int $notify_days
 * @property int|null $commit_request_deadline_days
 * @property User[] $users
 * @property Shift[] $shifts
 * @property bool $universally_applicable
 * @property int $position
 * @property bool $inventory_planned_by_all
 * @property Collection $craftShiftPlaner
 * @property Collection $craftInventoryPlaner
 * @property Collection $qualifications
 */
class Craft extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'assignable_by_all',
        'color',
        'notify_days',
        'commit_request_deadline_days',
        'universally_applicable',
        'position',
        'inventory_planned_by_all'
    ];

    protected $casts = [
        'assignable_by_all' => 'boolean',
        'commit_request_deadline_days' => 'integer',
        'universally_applicable' => 'boolean',
        'inventory_planned_by_all' => 'boolean'
    ];

    protected $with = ['craftShiftPlaner'];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function craftShiftPlaner(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'craft_users', 'craft_id', 'user_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function craftInventoryPlaner(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'craft_users_inventory', 'craft_id', 'user_id');
    }

    public function scopeIsAssignableByAll(Builder $builder): Builder
    {
        return $builder->where('assignable_by_all', '=', true);
    }

    /**
     * @return HasMany<Shift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'craft_id', 'id');
    }

    /**
     * @return BelongsToMany<ShiftQualification, $this>
     */
    public function qualifications(): BelongsToMany
    {
        return $this->belongsToMany(
            ShiftQualification::class,
            'craft_shift_qualification',
            'craft_id',
            'shift_qualification_id'
        );
    }

    /**
     * @return MorphToMany<User, $this>
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'craftable')
            ->where('can_work_shifts', true)
            ->without([
                'calendar_settings', 'calendarAbo', 'shiftCalendarAbo'
            ])->with(['shiftQualifications']);
    }

    /**
     * @return MorphToMany<Freelancer, $this>
     */
    public function freelancers(): MorphToMany
    {
        return $this->morphedByMany(Freelancer::class, 'craftable')
            ->where('can_work_shifts', true)
            ->with(['shiftQualifications']);
    }

    /**
     * @return MorphToMany<ServiceProvider, $this>
     */
    public function serviceProviders(): MorphToMany
    {
        return $this->morphedByMany(ServiceProvider::class, 'craftable')
            ->where('can_work_shifts', true)
            ->with(['shiftQualifications']);
    }

    /**
     * @return MorphToMany<User, $this>
     */
    public function managingUsers(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'craft_manager');
    }

    /**
     * @return MorphToMany<Freelancer, $this>
     */
    public function managingFreelancers(): MorphToMany
    {
        return $this->morphedByMany(Freelancer::class, 'craft_manager');
    }

    /**
     * @return MorphToMany<ServiceProvider, $this>
     */
    public function managingServiceProviders(): MorphToMany
    {
        return $this->morphedByMany(ServiceProvider::class, 'craft_manager');
    }

    /**
     * @return HasMany<ShiftPlanRequest, $this>
     */
    public function shiftPlanRequests(): HasMany
    {
        return $this->hasMany(
            ShiftPlanRequest::class,
            'craft_id',
            'id'
        );
    }
}

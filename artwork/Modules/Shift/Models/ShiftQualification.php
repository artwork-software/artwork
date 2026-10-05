<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\FreelancerShiftQualification;
use Artwork\Modules\Shift\Models\ServiceProviderShiftQualification;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\Shift\Models\UserShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property string $icon
 * @property string $name
 * @property bool $available
 * @property int $position
 */
class ShiftQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'icon',
        'name',
        'available',
        'position'
    ];

    protected $casts = [
        'available' => 'boolean',
        'position' => 'integer'
    ];

    /**
     * @return HasMany<ShiftsQualifications, $this>
     */
    public function shiftsQualifications(): HasMany
    {
        return $this->hasMany(ShiftsQualifications::class);
    }

    public function scopeAvailable(Builder $builder): Builder
    {
        return $builder->where('available', true);
    }

    public function scopeOrderByCreationDateAscending(Builder $builder): Builder
    {
        return $builder->orderBy('created_at');
    }

    public function scopeOrderedByPosition(Builder $builder): Builder
    {
        return $builder->orderBy('position')->orderBy('id');
    }

    public function scopeWorkerQualification(Builder $builder): Builder
    {
        return $builder->where('name', 'Mitarbeiter');
    }

    public function scopeMasterQualification(Builder $builder): Builder
    {
        return $builder->where('name', 'Meister');
    }

    /**
     * @return MorphToMany<\Artwork\Modules\User\Models\User, $this>
     */
    public function qualifiables(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(
            \Artwork\Modules\User\Models\User::class,
            'qualifiable',
            'shift_qualifiables',
            'shift_qualification_id',
            'qualifiable_id'
        )->withPivot('craft_id');
    }
    /**
     * @return MorphToMany<\Artwork\Modules\Freelancer\Models\Freelancer, $this>
     */
    public function freelancersMorph(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(
            \Artwork\Modules\Freelancer\Models\Freelancer::class,
            'qualifiable',
            'shift_qualifiables',
            'shift_qualification_id',
            'qualifiable_id'
        )->withPivot('craft_id');
    }
    /**
     * @return MorphToMany<\Artwork\Modules\ServiceProvider\Models\ServiceProvider, $this>
     */
    public function serviceProvidersMorph(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(
            \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class,
            'qualifiable',
            'shift_qualifiables',
            'shift_qualification_id',
            'qualifiable_id'
        )->withPivot('craft_id');
    }
}

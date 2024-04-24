<?php

namespace Artwork\Modules\ShiftQualification\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\ShiftsQualifications;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $icon
 * @property string $name
 * @property bool $available
 */
class ShiftQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'icon',
        'name',
        'available'
    ];

    protected $casts = [
        'available' => 'boolean'
    ];

    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'user_shift_qualifications')
            ->using(UserShiftQualification::class);
    }

    public function freelancers(): BelongsToMany
    {
        return $this
            ->belongsToMany(Freelancer::class, 'freelancer_shift_qualifications')
            ->using(FreelancerShiftQualification::class);
    }

    public function serviceProviders(): BelongsToMany
    {
        return $this
            ->belongsToMany(ServiceProvider::class, 'service_provider_shift_qualifications')
            ->using(ServiceProviderShiftQualification::class);
    }

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

    public function scopeWorkerQualification(Builder $builder): Builder
    {
        return $builder->where('name', 'Mitarbeiter');
    }

    public function scopeMasterQualification(Builder $builder): Builder
    {
        return $builder->where('name', 'Meister');
    }
}

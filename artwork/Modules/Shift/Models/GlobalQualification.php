<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\Shift\QueryBuilders\ShiftsQualificationsBuilder;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * * @property int $id
 * * @property string $name
 * * @property string $icon
 * * @property \Illuminate\Support\Carbon|null $created_at
 * * @property \Illuminate\Support\Carbon|null $updated_at
 * * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * * @property-read int|null $users_count
 */
class GlobalQualification extends Model
{
    /** @use HasFactory<\Database\Factories\Artwork\Modules\Shift\Models\GlobalQualificationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_global_qualifications',
            'user_id',
            'global_qualification_id'
        );
    }
    /**
     * @return MorphToMany<\Artwork\Modules\User\Models\User, $this>
     */
    public function qualifiables(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(
            \Artwork\Modules\User\Models\User::class,
            'qualifiable',
            'global_qualifiables',
            'global_qualification_id',
            'qualifiable_id'
        );
    }
    /**
     * @return MorphToMany<\Artwork\Modules\Freelancer\Models\Freelancer, $this>
     */
    public function freelancers(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(
            \Artwork\Modules\Freelancer\Models\Freelancer::class,
            'qualifiable',
            'global_qualifiables',
            'global_qualification_id',
            'qualifiable_id'
        );
    }
    /**
     * @return MorphToMany<\Artwork\Modules\ServiceProvider\Models\ServiceProvider, $this>
     */
    public function serviceProviders(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(
            \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class,
            'qualifiable',
            'global_qualifiables',
            'global_qualification_id',
            'qualifiable_id'
        );
    }

    public function newEloquentBuilder($query): ShiftsQualificationsBuilder
    {
        return new ShiftsQualificationsBuilder($query);
    }
}

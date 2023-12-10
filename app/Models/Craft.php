<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Craft extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'abbreviation',
        'assignable_by_all'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'assignable_by_all' => 'boolean'
    ];

    /**
     * @var string[]
     */
    protected $with = ['users'];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'craft_users');
    }

    /**
     * @return BelongsToMany
     */
    public function assigned_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_assigned_crafts');
    }

    /**
     * @return BelongsToMany
     */
    public function assigned_freelancers(): BelongsToMany
    {
        return $this->belongsToMany(Freelancer::class, 'freelancer_assigned_crafts');
    }

    /**
     * @return BelongsToMany
     */
    public function assigned_service_providers(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class, 'service_provider_assigned_crafts');
    }
}

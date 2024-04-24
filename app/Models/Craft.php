<?php

namespace App\Models;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property bool $assignable_by_all
 * @property string $created_at
 * @property string $updated_at
 */
class Craft extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbreviation',
        'assignable_by_all'
    ];

    protected $casts = [
        'assignable_by_all' => 'boolean'
    ];

    protected $with = ['users'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'craft_users');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_assigned_crafts');
    }

    public function assignedFreelancers(): BelongsToMany
    {
        return $this->belongsToMany(Freelancer::class, 'freelancer_assigned_crafts');
    }

    public function assignedServiceProviders(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class, 'service_provider_assigned_crafts');
    }
}

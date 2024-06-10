<?php

namespace Artwork\Modules\DayService\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
* @property int $id
 * @property string $name
 * @property string $icon
 * @property string $hex_color
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DayService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'hex_color',
    ];

    // Definieren Sie die Beziehung für jedes Modelltyp separat
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'day_serviceable')
            ->withTimestamps()
            ->withPivot('date');
    }

    public function freelancers(): MorphToMany
    {
        return $this->morphedByMany(Freelancer::class, 'day_serviceable')
            ->withTimestamps()
            ->withPivot('date');
    }

    public function serviceProviders(): MorphToMany
    {
        return $this->morphedByMany(ServiceProvider::class, 'day_serviceable')
            ->withTimestamps()
            ->withPivot('date');
    }
}

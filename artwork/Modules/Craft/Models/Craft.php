<?php

namespace Artwork\Modules\Craft\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function scopeIsAssignableByAll(Builder $builder): Builder
    {
        return $builder->where('assignable_by_all', '=', true);
    }
}

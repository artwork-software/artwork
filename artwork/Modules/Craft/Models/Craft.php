<?php

namespace Artwork\Modules\Craft\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\InventoryManagement\Models\CraftInventoryCategory;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property bool $assignable_by_all
 * @property string $created_at
 * @property string $updated_at
 * @property string $color
 * @property int $notify_days
 * @property User[] $users
 * @property Shift[] $shifts
 * @property Collection $inventoryCategories
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
        'universally_applicable'
    ];

    protected $casts = [
        'assignable_by_all' => 'boolean',
        'universally_applicable' => 'boolean',
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

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'craft_id', 'id');
    }

    public function inventoryCategories(): HasMany
    {
        return $this->hasMany(
            CraftInventoryCategory::class,
            'craft_id',
            'id'
        )
            ->orderBy('order')
            ->select(['id', 'craft_id', 'name', 'order']);
    }
}

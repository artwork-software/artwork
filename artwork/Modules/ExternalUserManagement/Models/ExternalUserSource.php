<?php

namespace Artwork\Modules\ExternalUserManagement\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property string $type
 * @property array|null $config
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<ExternalUser> $externalUsers
 * @property \Illuminate\Database\Eloquent\Collection<ExternalUserGroupMapping> $groupMappings
 */
class ExternalUserSource extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'active',
        'type',
        'config',
    ];

    protected $casts = [
        'active' => 'boolean',
        'config' => 'array',
    ];

    public function externalUsers(): HasMany
    {
        return $this->hasMany(ExternalUser::class, 'source_id', 'id');
    }

    public function groupMappings(): HasMany
    {
        return $this->hasMany(ExternalUserGroupMapping::class, 'source_id', 'id');
    }
}


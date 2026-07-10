<?php

namespace Artwork\Modules\ExternalUserManagement\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $source_id
 * @property string $ad_group_dn
 * @property string $ad_group_name
 * @property array|null $permission_ids
 * @property array|null $role_ids
 * @property bool $include_nested_groups
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property ExternalUserSource $source
 */
class ExternalUserGroupMapping extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'source_id',
        'ad_group_dn',
        'ad_group_name',
        'permission_ids',
        'role_ids',
        'include_nested_groups',
    ];

    protected $casts = [
        'permission_ids' => 'array',
        'role_ids' => 'array',
        'include_nested_groups' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ExternalUserSource::class, 'source_id', 'id');
    }
}


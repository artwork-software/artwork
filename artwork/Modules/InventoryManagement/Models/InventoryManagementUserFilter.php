<?php

namespace Artwork\Modules\InventoryManagement\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property array $filter
 */
class InventoryManagementUserFilter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'filter'
    ];

    protected $casts = [
        'filter' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'users'
        );
    }
}

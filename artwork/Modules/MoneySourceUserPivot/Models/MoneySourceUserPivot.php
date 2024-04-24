<?php

namespace Artwork\Modules\MoneySourceUserPivot\Models;

use Artwork\Core\Database\Models\Pivot;

/**
 * @property int $id
 * @property int $money_source_id
 * @property int $user_id
 * @property int $competent
 * @property int $write_access
 * @property string $created_at
 * @property string $updated_at
 */
class MoneySourceUserPivot extends Pivot
{
    protected $casts = [
        'competent' => 'boolean',
        'write_access' => 'boolean'
    ];
}

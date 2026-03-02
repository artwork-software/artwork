<?php

namespace Artwork\Modules\User\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $show_number
 */
class UserBudgetAccountDisplaySetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'show_number'
    ];

    protected $casts = [
        'show_number' => 'boolean'
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}

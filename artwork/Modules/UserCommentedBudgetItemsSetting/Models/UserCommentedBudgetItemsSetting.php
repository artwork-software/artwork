<?php

namespace Artwork\Modules\UserCommentedBudgetItemsSetting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $exclude
 */
class UserCommentedBudgetItemsSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'exclude'
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}

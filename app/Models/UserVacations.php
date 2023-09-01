<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserVacations
 * @property int $id
 * @property int $user_id
 * @property string $from
 *  @property string $until
 */
class UserVacations extends Model
{
    use HasFactory;
    use HasChangesHistory;


    protected $fillable = [
        'user_id',
        'from',
        'until'
    ];
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

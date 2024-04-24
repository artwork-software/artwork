<?php

namespace Artwork\Modules\UserShiftCalendarFilter\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $start_date
 * @property string $end_date
 * @property array $event_types
 * @property array $rooms
 * @property string $created_at
 * @property string $updated_at
 */
class UserShiftCalendarFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'event_types',
        'rooms'
    ];

    protected $casts = [
        'event_types' => 'array',
        'rooms' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}

<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historie manueller Überstunden-Auszahlungen (DP-18 Stufe 2).
 *
 * @property int $id
 * @property int $user_id
 * @property int $minutes
 * @property string $payout_date
 * @property int|null $created_by
 * @property string|null $comment
 */
class OvertimePayout extends Model
{
    protected $fillable = [
        'user_id',
        'minutes',
        'payout_date',
        'created_by',
        'comment',
    ];

    protected $casts = [
        'minutes' => 'integer',
        'payout_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

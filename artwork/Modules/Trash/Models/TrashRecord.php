<?php

namespace Artwork\Modules\Trash\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Merkt sich, wer einen Eintrag in den Papierkorb gelegt hat.
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property int|null $user_id
 */
class TrashRecord extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }
}

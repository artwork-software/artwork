<?php

namespace Artwork\Modules\User\Models;

use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property boolean $show_qualifications
 * @property boolean $shift_notes
 * @property boolean $show_shift_group_tag
 * @property boolean $show_fully_staffed_shifts
 * @property boolean $detailed_shift_overview
 * @property string $created_at
 * @property string $updated_at
 */
class UserShiftListViewSettings extends Model
{
    use HasFactory;

    protected $hidden = [
        'id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'show_qualifications',
        'shift_notes',
        'show_shift_group_tag',
        'show_fully_staffed_shifts',
        'detailed_shift_overview',
    ];

    protected $casts = [
        'show_qualifications' => 'boolean',
        'shift_notes' => 'boolean',
        'show_shift_group_tag' => 'boolean',
        'show_fully_staffed_shifts' => 'boolean',
        'detailed_shift_overview' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}

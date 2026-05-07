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
 * @property boolean $show_appointments
 * @property boolean $group_by_shift_groups
 * @property boolean $hide_shift_row
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
        'show_appointments',
        'group_by_shift_groups',
        'hide_shift_row',
    ];

    protected $casts = [
        'show_qualifications' => 'boolean',
        'shift_notes' => 'boolean',
        'show_shift_group_tag' => 'boolean',
        'show_fully_staffed_shifts' => 'boolean',
        'detailed_shift_overview' => 'boolean',
        'show_appointments' => 'boolean',
        'group_by_shift_groups' => 'boolean',
        'hide_shift_row' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}

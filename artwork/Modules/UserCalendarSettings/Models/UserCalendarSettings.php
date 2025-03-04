<?php

namespace Artwork\Modules\UserCalendarSettings\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property boolean $project_status
 * @property boolean $project_artists
 * @property boolean $description
 * @property boolean $options
 * @property boolean $project_management
 * @property boolean $repeating_events
 * @property boolean $use_project_time_period
 * @property int $time_period_project_id
 * @property boolean $event_name
 * @property boolean $high_contrast
 * @property boolean $expand_days
 * @property boolean $use_event_status_color
 * @property boolean $work_shifts
 * @property string $created_at
 * @property string $updated_at
 */
class UserCalendarSettings extends Model
{
    use HasFactory;

    protected $hidden = [
        'id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'project_artists',
        'project_status',
        'options',
        'project_management',
        'repeating_events',
        'work_shifts',
        'description',
        'use_project_time_period',
        'time_period_project_id',
        'event_name',
        'high_contrast',
        'expand_days',
        'use_event_status_color',
        'show_qualifications',
        'shift_notes',
        'hide_unoccupied_rooms',
        'display_project_groups',
    ];

    protected $casts = [
        'project_artists' => 'boolean',
        'project_status' => 'boolean',
        'options' => 'boolean',
        'project_management' => 'boolean',
        'repeating_events' => 'boolean',
        'work_shifts' => 'boolean',
        'description' => 'boolean',
        'use_project_time_period' => 'boolean',
        'event_name' => 'boolean',
        'high_contrast' => 'boolean',
        'expand_days' => 'boolean',
        'use_event_status_color' => 'boolean',
        'show_qualifications' => 'boolean',
        'shift_notes' => 'boolean',
        'hide_unoccupied_rooms' => 'boolean',
        'display_project_groups' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property boolean $project_status
 * @property boolean $options
 * @property boolean $project_management
 * @property boolean $repeating_events
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
        'project_status',
        'options',
        'project_management',
        'repeating_events',
        'work_shifts'
    ];

    protected $casts = [
        'project_status' => 'boolean',
        'options' => 'boolean',
        'project_management' => 'boolean',
        'repeating_events' => 'boolean',
        'work_shifts' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

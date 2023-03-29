<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property boolean $project_status
 * @property boolean $options
 * @property boolean $project_management
 * @property boolean $repeating_events
 * @property boolean $work_shifts
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

    /**
     * @var string[]
     */
    protected $fillable = [
        'project_status',
        'options',
        'project_management',
        'repeating_events',
        'work_shifts'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'project_status' => 'boolean',
        'options' => 'boolean',
        'project_management' => 'boolean',
        'repeating_events' => 'boolean',
        'work_shifts' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

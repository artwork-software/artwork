<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property boolean $project_status
 * @property boolean $option_prioritization
 * @property boolean $project_management
 * @property boolean $repeat_date
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
        'option_prioritization',
        'project_management',
        'repeat_date',
        'work_shifts'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'project_status' => 'boolean',
        'option_prioritization' => 'boolean',
        'project_management' => 'boolean',
        'repeat_date' => 'boolean',
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

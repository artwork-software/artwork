<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftCommitWorkflowRequests extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftCommitWorkflowRequestsFactory> */
    use HasFactory;


    protected $fillable = [
        'requested_by_id',
        'start_date',
        'end_date',
        'approved_by_id',
        'declined_by_id',
        'status',
        'reason',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_by_id' => 'integer',
        'approved_by_id' => 'integer',
        'declined_by_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'string',
        'reason' => 'string'
    ];

    /**
     * Get the user who requested the shift commit workflow.
     */
    public function requestedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id', 'id', 'requested_by');
    }

    /**
     * Get the user who approved the shift commit workflow.
     */
    public function approvedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id', 'id', 'approved_by');
    }

    /**
     * Get the user who declined the shift commit workflow.
     */
    public function declinedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'declined_by_id', 'id', 'declined_by');
    }
}

<?php

namespace Artwork\Modules\Shift\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftCommitWorkflowUser extends Model
{
    /** @use HasFactory<\Database\Factories\ShiftCommitWorkflowUserFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * Get the user associated with the shift commit workflow user.
     */

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }
}

<?php

namespace Artwork\Modules\ExternalAccess\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $submission_id
 * @property string $target_type
 * @property int $target_id
 * @property string $field_key
 * @property mixed $old_value
 * @property mixed $new_value
 * @property FieldApprovalStatus $approval_status
 * @property Carbon|null $reviewed_at
 */
class ExternalPendingFieldChange extends Model
{
    protected $fillable = [
        'submission_id',
        'target_type',
        'target_id',
        'field_key',
        'old_value',
        'new_value',
        'approval_status',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'json',
            'new_value' => 'json',
            'reviewed_at' => 'datetime',
            'approval_status' => FieldApprovalStatus::class,
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ExternalPendingSubmission::class, 'submission_id', 'id', 'submission');
    }

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}

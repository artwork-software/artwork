<?php

namespace Artwork\Modules\ExternalAccess\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $external_access_id
 * @property ExternalSubmissionContext $context
 * @property ExternalSubmissionStatus $status
 * @property Carbon $submitted_at
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 */
class ExternalPendingSubmission extends Model
{
    protected $fillable = [
        'external_access_id',
        'context',
        'status',
        'submitted_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'status' => ExternalSubmissionStatus::class,
            'context' => ExternalSubmissionContext::class,
        ];
    }

    public function externalAccess(): BelongsTo
    {
        return $this->belongsTo(ExternalAccess::class, 'external_access_id', 'id', 'externalAccess');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'id', 'reviewer');
    }

    public function fieldChanges(): HasMany
    {
        return $this->hasMany(ExternalPendingFieldChange::class, 'submission_id');
    }

    public function scopeCurrentPending(Builder $query, ExternalAccess $external): Builder
    {
        return $query
            ->where('external_access_id', $external->id)
            ->where('context', ExternalSubmissionContext::CRM_SELF)
            ->where('status', ExternalSubmissionStatus::PENDING);
    }
}

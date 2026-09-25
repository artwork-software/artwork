<?php

namespace Artwork\Modules\ExternalAccess\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\Artwork\Modules\ExternalAccess\Models\ExternalAccessScopeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $external_access_id
 * @property int $project_id
 * @property int $project_tab_id
 * @property ExternalAccessType $access_type
 * @property Carbon $valid_from
 * @property Carbon $valid_to
 * @property Carbon|null $last_submitted_at
 * @property ExternalTabSubmissionStatus $submission_status
 * @property Carbon|null $reviewed_at
 * @property int|null $reviewed_by_user_id
 * @property string|null $review_comment
 * @property Carbon|null $expiry_reminder_sent_at
 * @property int|null $granted_by_user_id
 */
class ExternalAccessScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_access_id',
        'project_id',
        'project_tab_id',
        'access_type',
        'valid_from',
        'valid_to',
        'last_submitted_at',
        'submission_status',
        'reviewed_at',
        'reviewed_by_user_id',
        'review_comment',
        'expiry_reminder_sent_at',
        'granted_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'last_submitted_at' => 'datetime',
            'submission_status' => ExternalTabSubmissionStatus::class,
            'reviewed_at' => 'datetime',
            'expiry_reminder_sent_at' => 'datetime',
            'access_type' => ExternalAccessType::class,
        ];
    }

    protected static function newFactory(): ExternalAccessScopeFactory
    {
        return ExternalAccessScopeFactory::new();
    }

    public function externalAccess(): BelongsTo
    {
        return $this->belongsTo(ExternalAccess::class, 'external_access_id', 'id', 'externalAccess');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }

    public function projectTab(): BelongsTo
    {
        return $this->belongsTo(ProjectTab::class, 'project_tab_id', 'id', 'projectTab');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id', 'id', 'grantedBy');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id', 'id', 'reviewedBy');
    }

    /**
     * Nach dem Absenden (bis zur Rückgabe) darf die externe Person im Tab nichts mehr ändern.
     */
    public function isLockedForExternal(): bool
    {
        return ($this->submission_status ?? ExternalTabSubmissionStatus::OPEN)->locksExternalEditing();
    }

    public function scopeCurrentlyValid(Builder $query): Builder
    {
        return $query
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>=', now());
    }
}

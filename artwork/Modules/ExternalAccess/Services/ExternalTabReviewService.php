<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Enums\ExternalTabSubmissionStatus;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectComponentCrmContactService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/**
 * Interne Prüfung abgesendeter Tabs: bestätigen (bleibt gesperrt, extern angelegte Kontakte gelten als
 * geprüft) oder zur Überarbeitung zurückgeben (wieder bearbeitbar, optional mit Kommentar). In beiden
 * Fällen bekommt die externe Person eine Mail.
 */
class ExternalTabReviewService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ExternalNotificationSender $notificationSender,
        private readonly ProjectComponentCrmContactService $crmContactService,
    ) {
    }

    /**
     * Alle Externen mit (noch nicht abgelaufenem) Zugang zu diesem Tab in diesem Projekt.
     *
     * @return Collection<int, ExternalAccessScope>
     */
    public function scopesFor(Project $project, ProjectTab $tab): Collection
    {
        return ExternalAccessScope::query()
            ->where('project_id', $project->id)
            ->where('project_tab_id', $tab->id)
            ->where('valid_to', '>=', now())
            ->whereHas('externalAccess', fn ($query) => $query->whereNull('revoked_at'))
            ->with([
                'externalAccess.crmContact',
                'reviewedBy:id,first_name,last_name',
                'grantedBy:id,first_name,last_name',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Einladende Person (bzw. wer den Tab freigegeben hat) oder alle mit Schreibrecht im Projekt.
     */
    public function canReview(User $user, ExternalAccessScope $scope, Project $project): bool
    {
        if ($user->can('update', $project)) {
            return true;
        }

        $inviterIds = array_filter([
            (int) $scope->granted_by_user_id,
            (int) $scope->externalAccess?->invited_by_user_id,
        ]);

        return in_array((int) $user->id, $inviterIds, true);
    }

    /**
     * @throws ValidationException
     */
    public function confirm(ExternalAccessScope $scope, User $reviewer): ExternalAccessScope
    {
        $this->assertStatus($scope, [ExternalTabSubmissionStatus::SUBMITTED]);

        $this->db->transaction(function () use ($scope, $reviewer): void {
            $scope->forceFill([
                'submission_status' => ExternalTabSubmissionStatus::CONFIRMED,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewer->id,
                'review_comment' => null,
            ])->save();

            $project = $scope->project()->firstOrFail();
            $this->crmContactService->markReviewedForExternal($project, $scope->externalAccess, $reviewer);
            $this->logProjectHistory($scope, $reviewer, 'Data of external person {0} in tab {1} was confirmed');
        });

        $this->notificationSender->notifyExternalTabReviewed($scope->refresh());

        return $scope;
    }

    /**
     * Zurückgeben geht aus „abgesendet“ und aus „bestätigt“ (falls doch noch etwas fehlt).
     *
     * @throws ValidationException
     */
    public function returnForRevision(ExternalAccessScope $scope, User $reviewer, ?string $comment): ExternalAccessScope
    {
        $this->assertStatus($scope, [ExternalTabSubmissionStatus::SUBMITTED, ExternalTabSubmissionStatus::CONFIRMED]);

        $this->db->transaction(function () use ($scope, $reviewer, $comment): void {
            $comment = $comment !== null && trim($comment) !== '' ? trim($comment) : null;
            $scope->forceFill([
                'submission_status' => ExternalTabSubmissionStatus::RETURNED,
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewer->id,
                'review_comment' => $comment,
            ])->save();

            $this->logProjectHistory(
                $scope,
                $reviewer,
                'Data of external person {0} in tab {1} was returned for revision',
            );
        });

        $this->notificationSender->notifyExternalTabReviewed($scope->refresh());

        return $scope;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(ExternalAccessScope $scope, User $viewer, Project $project): array
    {
        $external = $scope->externalAccess;

        return [
            'scope_id' => $scope->id,
            'name' => $external->displayName(),
            'email' => $external->email,
            'access_type' => $scope->access_type->value,
            'can_write' => $scope->access_type === ExternalAccessType::WRITE,
            'status' => ($scope->submission_status ?? ExternalTabSubmissionStatus::OPEN)->value,
            'last_login_at' => $external->last_login_at?->toIso8601String(),
            'last_submitted_at' => $scope->last_submitted_at?->toIso8601String(),
            'reviewed_at' => $scope->reviewed_at?->toIso8601String(),
            'reviewed_by' => $scope->reviewedBy?->full_name,
            'review_comment' => $scope->review_comment,
            'valid_to' => $scope->valid_to->toIso8601String(),
            'invited_by' => $scope->grantedBy?->full_name,
            'can_review' => $this->canReview($viewer, $scope, $project),
        ];
    }

    /**
     * @param array<int, ExternalTabSubmissionStatus> $allowed
     * @throws ValidationException
     */
    private function assertStatus(ExternalAccessScope $scope, array $allowed): void
    {
        if (!in_array($scope->submission_status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('The data has not been submitted in this state.'),
            ]);
        }
    }

    private function logProjectHistory(ExternalAccessScope $scope, User $reviewer, string $translationKey): void
    {
        $scope->loadMissing(['externalAccess', 'projectTab']);

        Activity::query()->create([
            'log_name' => 'project',
            'description' => $translationKey,
            'subject_type' => (new Project())->getMorphClass(),
            'subject_id' => $scope->project_id,
            'event' => 'updated',
            'causer_type' => $reviewer->getMorphClass(),
            'causer_id' => $reviewer->id,
            'properties' => [[
                'type' => 'project',
                'translationKey' => $translationKey,
                'translationKeyPlaceholderValues' => [
                    $scope->externalAccess?->displayName(),
                    $scope->projectTab?->name,
                ],
            ]],
        ]);
    }
}

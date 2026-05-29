<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Exceptions\EmailAlreadyInUseException;
use Artwork\Modules\ExternalAccess\Exceptions\NotAuthorizedToReviewException;
use Artwork\Modules\ExternalAccess\Exceptions\SubmissionAlreadyDecidedException;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

class ExternalSubmissionApprovalService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ExternalNotificationSender $notificationSender,
    ) {
    }

    public function approveAll(ExternalPendingSubmission $submission, User $reviewer): void
    {
        $this->db->transaction(function () use ($submission, $reviewer): void {
            $submission = $this->lockAndEnsureReviewable($submission, $reviewer);
            $external = $submission->externalAccess;

            $pendingChanges = $submission->fieldChanges()
                ->where('approval_status', FieldApprovalStatus::PENDING)
                ->get();
            foreach ($pendingChanges as $change) {
                $this->applyFieldChange($change, $external);
                $change->update(['approval_status' => FieldApprovalStatus::APPROVED, 'reviewed_at' => now()]);
            }

            $submission->update([
                'status' => ExternalSubmissionStatus::APPROVED,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $this->triggerSyncToCrmForApprovedTargets($submission);
            $this->logApproval($submission, $reviewer, 'approved_all');
        });

        $this->notificationSender->notifyExternalReviewResult($submission->fresh());
    }

    public function rejectAll(ExternalPendingSubmission $submission, User $reviewer, ?string $reason = null): void
    {
        $this->db->transaction(function () use ($submission, $reviewer, $reason): void {
            $submission = $this->lockAndEnsureReviewable($submission, $reviewer);

            $submission->fieldChanges()
                ->where('approval_status', FieldApprovalStatus::PENDING)
                ->update(['approval_status' => FieldApprovalStatus::REJECTED, 'reviewed_at' => now()]);

            $submission->update([
                'status' => ExternalSubmissionStatus::REJECTED,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->logApproval($submission, $reviewer, 'rejected_all');
        });

        $this->notificationSender->notifyExternalReviewResult($submission->fresh());
    }

    /**
     * @param array<int, FieldApprovalStatus> $decisions field_change_id => decision
     */
    public function applyPartialDecisions(
        ExternalPendingSubmission $submission,
        User $reviewer,
        array $decisions,
        ?string $rejectionReason = null,
    ): void {
        $this->db->transaction(function () use ($submission, $reviewer, $decisions, $rejectionReason): void {
            $submission = $this->lockAndEnsureReviewable($submission, $reviewer);
            $external = $submission->externalAccess;

            $hasApproved = false;
            $hasRejected = false;

            foreach ($decisions as $fieldChangeId => $decision) {
                $change = $submission->fieldChanges()
                    ->where('id', $fieldChangeId)
                    ->where('approval_status', FieldApprovalStatus::PENDING)
                    ->first();

                if (!$change) {
                    continue;
                }

                if ($decision === FieldApprovalStatus::APPROVED) {
                    $this->applyFieldChange($change, $external);
                    $hasApproved = true;
                } else {
                    $hasRejected = true;
                }

                $change->update(['approval_status' => $decision, 'reviewed_at' => now()]);
            }

            // Any field changes left undecided keep the submission open conceptually, but the
            // reviewer action closes it with a final aggregate status.
            $finalStatus = match (true) {
                $hasApproved && $hasRejected => ExternalSubmissionStatus::PARTIALLY_APPROVED,
                $hasApproved => ExternalSubmissionStatus::APPROVED,
                $hasRejected => ExternalSubmissionStatus::REJECTED,
                default => $submission->status,
            };

            $submission->update([
                'status' => $finalStatus,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            if ($hasApproved) {
                $this->triggerSyncToCrmForApprovedTargets($submission);
            }

            $this->logApproval($submission, $reviewer, 'partial_decision');
        });

        $this->notificationSender->notifyExternalReviewResult($submission->fresh());
    }

    private function applyFieldChange(ExternalPendingFieldChange $change, ExternalAccess $external): void
    {
        $targetClass = $change->target_type;
        /** @var Model $target */
        $target = $targetClass::findOrFail($change->target_id);

        if (str_starts_with($change->field_key, 'crm_property:')) {
            $propertyId = (int) substr($change->field_key, strlen('crm_property:'));
            $property = CrmProperty::with('group')->findOrFail($propertyId);
            if ($property->group->is_confidential) {
                throw new \DomainException('Refuse to apply confidential property change');
            }
            CrmPropertyValue::updateOrCreate(
                ['crm_contact_id' => $target->id, 'crm_property_id' => $propertyId],
                ['value' => $change->new_value],
            );

            return;
        }

        $allowedFields = $this->getAllowedFieldsFor($target);
        if (!in_array($change->field_key, $allowedFields, true)) {
            throw new \DomainException("Field {$change->field_key} not allowed on {$targetClass}");
        }

        // Email edge case: keep ExternalAccess.email in sync and guard uniqueness.
        if ($change->field_key === 'email') {
            $newEmail = mb_strtolower(trim((string) $change->new_value));
            $conflict = ExternalAccess::query()
                ->whereKeyNot($external->id)
                ->whereRaw('LOWER(email) = ?', [$newEmail])
                ->exists();
            if ($conflict) {
                throw EmailAlreadyInUseException::forEmail($newEmail);
            }
            $target->update([$change->field_key => $change->new_value]);
            $external->forceFill(['email' => $newEmail])->save();

            return;
        }

        $target->update([$change->field_key => $change->new_value]);
    }

    /**
     * @return list<string>
     */
    private function getAllowedFieldsFor(Model $target): array
    {
        return match ($target::class) {
            Freelancer::class => ['first_name', 'last_name', 'email', 'phone_number', 'street', 'zip_code', 'location'],
            ServiceProvider::class => ['provider_name', 'email', 'phone_number', 'street', 'zip_code', 'location'],
            default => [],
        };
    }

    private function triggerSyncToCrmForApprovedTargets(ExternalPendingSubmission $submission): void
    {
        $targets = $submission->fieldChanges()
            ->where('approval_status', FieldApprovalStatus::APPROVED)
            ->get()
            ->groupBy(fn (ExternalPendingFieldChange $c) => $c->target_type . ':' . $c->target_id);

        foreach ($targets as $key => $changes) {
            [$type, $id] = explode(':', (string) $key, 2);
            if ($type === (new CrmContact())->getMorphClass() || $type === CrmContact::class) {
                continue; // CRM property values are written directly
            }
            $target = $type::find($id);
            if ($target && method_exists($target, 'syncToCrm')) {
                $target->syncToCrm();
            }
        }
    }

    private function lockAndEnsureReviewable(
        ExternalPendingSubmission $submission,
        User $reviewer,
    ): ExternalPendingSubmission {
        /** @var ExternalPendingSubmission $locked */
        $locked = ExternalPendingSubmission::query()
            ->whereKey($submission->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($locked->status !== ExternalSubmissionStatus::PENDING) {
            throw SubmissionAlreadyDecidedException::make();
        }

        $invitedById = $locked->externalAccess->invited_by_user_id;
        $isInviter = $invitedById !== null && $invitedById === $reviewer->id;
        $isAdmin = $reviewer->hasRole(RoleEnum::ARTWORK_ADMIN->value);

        if (!$isInviter && !$isAdmin) {
            throw NotAuthorizedToReviewException::make();
        }

        return $locked;
    }

    private function logApproval(ExternalPendingSubmission $submission, User $reviewer, string $event): void
    {
        activity('crm_self_edit')
            ->performedOn($submission)
            ->causedBy($reviewer)
            ->withProperties([
                'rejection_reason' => $submission->rejection_reason,
                'field_count' => $submission->fieldChanges()->count(),
            ])
            ->log($event);
    }
}

<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
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
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

class ExternalSubmissionApprovalService
{
    /** Text-Pfad für ein Upload-Feld aus der Zeit vor dem Datei-Feld: wird nie übernommen. */
    public const NOT_APPLICABLE_LEGACY_UPLOAD = 'legacy_upload';

    /** Die vorläufige Datei der Einreichung liegt nicht mehr auf der Disk. */
    public const NOT_APPLICABLE_FILE_MISSING = 'file_missing';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ExternalNotificationSender $notificationSender,
        private readonly ExternalSelfEditFileService $fileService,
    ) {
    }

    /**
     * Übernimmt alle offenen Änderungen. Nicht übernehmbare Zeilen (Altbestand: Text-Pfad für ein
     * Upload-Feld, oder die vorläufige Datei fehlt) blockieren die übrigen nicht: sie werden
     * übersprungen und abgelehnt.
     *
     * @return int Anzahl der nicht übernehmbaren Änderungen
     */
    public function approveAll(ExternalPendingSubmission $submission, User $reviewer): int
    {
        $fileOperations = self::emptyFileOperations();

        $skipped = $this->runWithFileOperations($fileOperations, function () use (
            $submission,
            $reviewer,
            &$fileOperations,
        ): int {
            $submission = $this->lockAndEnsureReviewable($submission, $reviewer);
            $external = $submission->externalAccess;

            $applied = 0;
            $skipped = 0;
            $pendingChanges = $submission->fieldChanges()
                ->where('approval_status', FieldApprovalStatus::PENDING)
                ->get();
            foreach ($pendingChanges as $change) {
                $wasApplied = $this->applyFieldChange($change, $external, $fileOperations);
                if ($wasApplied) {
                    $applied++;
                } else {
                    $skipped++;
                }
                $change->update([
                    'approval_status' => $wasApplied ? FieldApprovalStatus::APPROVED : FieldApprovalStatus::REJECTED,
                    'reviewed_at' => now(),
                ]);
            }

            $submission->update([
                'status' => match (true) {
                    $skipped === 0 => ExternalSubmissionStatus::APPROVED,
                    $applied > 0 => ExternalSubmissionStatus::PARTIALLY_APPROVED,
                    default => ExternalSubmissionStatus::REJECTED,
                },
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $this->triggerSyncToCrmForApprovedTargets($submission);
            $this->logApproval($submission, $reviewer, 'approved_all');

            return $skipped;
        });

        $this->notificationSender->notifyExternalReviewResult($submission->fresh());

        return $skipped;
    }

    public function rejectAll(ExternalPendingSubmission $submission, User $reviewer, ?string $reason = null): void
    {
        $fileOperations = self::emptyFileOperations();

        $this->runWithFileOperations($fileOperations, function () use (
            $submission,
            $reviewer,
            $reason,
            &$fileOperations,
        ): void {
            $submission = $this->lockAndEnsureReviewable($submission, $reviewer);

            $pendingChanges = $submission->fieldChanges()
                ->where('approval_status', FieldApprovalStatus::PENDING)
                ->get();
            foreach ($pendingChanges as $change) {
                $fileOperations['delete_pending'][] = $change->new_value;
            }

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
     * @return int Anzahl der angenommenen, aber nicht übernehmbaren Änderungen
     */
    public function applyPartialDecisions(
        ExternalPendingSubmission $submission,
        User $reviewer,
        array $decisions,
        ?string $rejectionReason = null,
    ): int {
        $fileOperations = self::emptyFileOperations();

        $skipped = $this->runWithFileOperations($fileOperations, function () use (
            $submission,
            $reviewer,
            $decisions,
            $rejectionReason,
            &$fileOperations,
        ): int {
            $submission = $this->lockAndEnsureReviewable($submission, $reviewer);
            $external = $submission->externalAccess;

            $hasApproved = false;
            $hasRejected = false;
            $skipped = 0;

            foreach ($decisions as $fieldChangeId => $decision) {
                $change = $submission->fieldChanges()
                    ->where('id', $fieldChangeId)
                    ->where('approval_status', FieldApprovalStatus::PENDING)
                    ->first();

                if (!$change) {
                    continue;
                }

                if ($decision === FieldApprovalStatus::APPROVED) {
                    if ($this->applyFieldChange($change, $external, $fileOperations)) {
                        $hasApproved = true;
                    } else {
                        $decision = FieldApprovalStatus::REJECTED;
                        $hasRejected = true;
                        $skipped++;
                    }
                } else {
                    $fileOperations['delete_pending'][] = $change->new_value;
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

            return $skipped;
        });

        $this->notificationSender->notifyExternalReviewResult($submission->fresh());

        return $skipped;
    }

    /**
     * Ob eine Upload-Änderung nicht übernommen werden kann: Altbestand (Text-Pfad aus der Zeit vor dem
     * Datei-Feld) oder die vorläufige Datei liegt nicht mehr auf der Disk.
     */
    public function notApplicableUploadReason(ExternalPendingFieldChange $change): ?string
    {
        $parsed = $this->fileService->parse($change->new_value);
        if ($parsed === null) {
            return self::NOT_APPLICABLE_LEGACY_UPLOAD;
        }

        if (
            $parsed['kind'] === ExternalSelfEditFileService::KIND_UPLOAD
            && $change->approval_status === FieldApprovalStatus::PENDING
            && $this->fileService->existingPendingPathOf($change->new_value) === null
        ) {
            return self::NOT_APPLICABLE_FILE_MISSING;
        }

        return null;
    }

    /**
     * @return array{promoted: list<string>, delete_pending: list<mixed>, delete_property_files: list<mixed>}
     */
    private static function emptyFileOperations(): array
    {
        return ['promoted' => [], 'delete_pending' => [], 'delete_property_files' => []];
    }

    /**
     * Führt die Freigabe in einer Transaktion aus. Dateien werden erst nach dem Commit gelöscht; scheitert
     * die Transaktion, werden nur die schon nach crm-property-files kopierten Dateien wieder entfernt.
     *
     * @template TResult
     * @param array{promoted: list<string>, delete_pending: list<mixed>, delete_property_files: list<mixed>} $fileOps
     * @param callable(): TResult $callback
     * @return TResult
     */
    private function runWithFileOperations(array &$fileOps, callable $callback): mixed
    {
        try {
            $result = $this->db->transaction($callback);
        } catch (\Throwable $exception) {
            $this->fileService->deleteCopiedPropertyFiles($fileOps['promoted']);

            throw $exception;
        }

        foreach ($fileOps['delete_pending'] as $value) {
            $this->fileService->deletePending($value);
        }
        foreach ($fileOps['delete_property_files'] as $value) {
            $this->fileService->deletePropertyFile($value);
        }

        return $result;
    }

    /**
     * @param array{promoted: list<string>, delete_pending: list<mixed>, delete_property_files: list<mixed>} $fileOps
     * @return bool false when the change cannot be applied (skipped, nothing written)
     */
    private function applyFieldChange(
        ExternalPendingFieldChange $change,
        ExternalAccess $external,
        array &$fileOps,
    ): bool {
        $targetClass = $change->target_type;
        /** @var Model $target */
        $target = $targetClass::findOrFail($change->target_id);

        if (str_starts_with($change->field_key, 'crm_property:')) {
            $propertyId = (int) substr($change->field_key, strlen('crm_property:'));
            $property = CrmProperty::with('group')->findOrFail($propertyId);
            if ($property->group->is_confidential) {
                throw new \DomainException('Refuse to apply confidential property change');
            }
            if ($property->type === CrmPropertyTypeEnum::UPLOAD) {
                return $this->applyUploadChange($change, (int) $target->getKey(), $propertyId, $fileOps);
            }
            CrmPropertyValue::updateOrCreate(
                ['crm_contact_id' => $target->id, 'crm_property_id' => $propertyId],
                ['value' => $change->new_value],
            );

            return true;
        }

        $allowedFields = ExternalSelfEditFieldResolver::allowedFieldsFor($target::class);
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

            return true;
        }

        $target->update([$change->field_key => $change->new_value]);

        return true;
    }

    /**
     * Neue Datei: vorläufige Datei nach crm-property-files übernehmen, alte Datei löschen.
     * Entfernen: Wert leeren, alte Datei löschen. Ein Text-Pfad (Altbestand) wird nie übernommen.
     *
     * @param array{promoted: list<string>, delete_pending: list<mixed>, delete_property_files: list<mixed>} $fileOps
     */
    private function applyUploadChange(
        ExternalPendingFieldChange $change,
        int $contactId,
        int $propertyId,
        array &$fileOps,
    ): bool {
        $parsed = $this->fileService->parse($change->new_value);
        if ($parsed === null) {
            return false; // Altbestand: Text-Pfad
        }

        $newPath = null;
        if ($parsed['kind'] === ExternalSelfEditFileService::KIND_UPLOAD) {
            $pendingPath = $this->fileService->existingPendingPathOf($change->new_value);
            if ($pendingPath === null) {
                return false; // vorläufige Datei fehlt
            }
            $newPath = $this->fileService->promote($pendingPath);
            $fileOps['promoted'][] = $newPath;
            $fileOps['delete_pending'][] = $change->new_value;
        }

        $previousValue = CrmPropertyValue::query()
            ->where('crm_contact_id', $contactId)
            ->where('crm_property_id', $propertyId)
            ->value('value');

        CrmPropertyValue::updateOrCreate(
            ['crm_contact_id' => $contactId, 'crm_property_id' => $propertyId],
            ['value' => $newPath],
        );
        $fileOps['delete_property_files'][] = $previousValue;

        return true;
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
                continue; // CRM property values / display name live on the contact itself
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

<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Enums\SelfEditSectionMode;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

class ExternalSelfEditSubmissionService
{
    public function __construct(
        private readonly ExternalSelfEditFieldResolver $resolver,
        private readonly DatabaseManager $db,
        private readonly ExternalNotificationSender $notificationSender,
    ) {
    }

    /**
     * Processes a submit of the self-edit form.
     *
     * Every changed field (source entity columns AND CRM property values) goes into ONE new
     * pending submission (an older pending one is superseded). Nothing is written to the CRM
     * until the inviting person approves. Returns null when nothing changed.
     *
     * @param array<string, array<string, mixed>> $submittedValues sectionKey => (fieldKey => value)
     */
    public function submit(ExternalAccess $external, array $submittedValues): ?ExternalPendingSubmission
    {
        $schema = $this->resolver->resolveFor($external);
        $isFirstSubmission = $this->isFirstSubmissionFor($external);

        $result = $this->db->transaction(function () use ($external, $schema, $submittedValues) {
            $stagedChanges = [];

            foreach ($schema->sections as $section) {
                foreach ($section->fields as $field) {
                    // A field absent from the payload is treated as "no change" (keeps the
                    // current value). Required validation only applies to fields that were
                    // actually submitted but left empty.
                    $submitted = array_key_exists($section->key, $submittedValues)
                        && array_key_exists($field->key, $submittedValues[$section->key]);

                    if (!$submitted) {
                        continue;
                    }

                    $newValue = $submittedValues[$section->key][$field->key];

                    if ($field->required && ($newValue === null || $newValue === '')) {
                        throw ValidationException::withMessages([
                            "values.{$section->key}.{$field->key}" => __('This field is required.'),
                        ]);
                    }

                    if ($this->normalizeValue($newValue) === $this->normalizeValue($field->value)) {
                        continue; // unchanged
                    }

                    $change = [
                        'target_type' => $section->targetType,
                        'target_id' => $section->targetId,
                        'field_key' => $field->key,
                        'old_value' => $field->value,
                        'new_value' => $newValue,
                    ];

                    if ($section->mode !== SelfEditSectionMode::STAGED) {
                        // Defense in depth: seit der Freigabe-Vereinheitlichung gibt es keine
                        // Direktschreib-Sektionen mehr; ein solcher Schema-Eintrag wäre ein Fehler.
                        throw new \DomainException("Section {$section->key} is not staged");
                    }

                    $stagedChanges[] = $change;
                }
            }

            $submission = null;
            if ($stagedChanges !== []) {
                $this->markExistingPendingAsSuperseded($external);
                $submission = $this->createPendingSubmission($external, $stagedChanges);
            }

            return ['submission' => $submission];
        });

        // Notifications are dispatched AFTER commit so a mail/queue failure never rolls back
        // the persisted changes.
        if ($result['submission'] !== null) {
            $this->notificationSender->notifyCrmSubmissionCreated($result['submission'], $isFirstSubmission);
        }

        return $result['submission'];
    }

    private function isFirstSubmissionFor(ExternalAccess $external): bool
    {
        return ExternalPendingSubmission::query()
            ->where('external_access_id', $external->id)
            ->where('context', ExternalSubmissionContext::CRM_SELF)
            ->whereIn('status', [
                ExternalSubmissionStatus::APPROVED,
                ExternalSubmissionStatus::PARTIALLY_APPROVED,
            ])
            ->doesntExist();
    }

    private function markExistingPendingAsSuperseded(ExternalAccess $external): void
    {
        $superseded = ExternalPendingSubmission::query()
            ->where('external_access_id', $external->id)
            ->where('context', ExternalSubmissionContext::CRM_SELF)
            ->where('status', ExternalSubmissionStatus::PENDING)
            ->get();

        foreach ($superseded as $submission) {
            $submission->update(['status' => ExternalSubmissionStatus::SUPERSEDED]);

            activity('crm_self_edit')
                ->performedOn($submission)
                ->causedBy($external)
                ->log('submission_superseded');
        }
    }

    /**
     * @param list<array<string, mixed>> $stagedChanges
     */
    private function createPendingSubmission(ExternalAccess $external, array $stagedChanges): ExternalPendingSubmission
    {
        $submission = ExternalPendingSubmission::create([
            'external_access_id' => $external->id,
            'context' => ExternalSubmissionContext::CRM_SELF,
            'status' => ExternalSubmissionStatus::PENDING,
            'submitted_at' => now(),
        ]);

        foreach ($stagedChanges as $change) {
            ExternalPendingFieldChange::create([
                'submission_id' => $submission->id,
                'target_type' => $change['target_type'],
                'target_id' => $change['target_id'],
                'field_key' => $change['field_key'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
                'approval_status' => FieldApprovalStatus::PENDING,
            ]);
        }

        activity('crm_self_edit')
            ->performedOn($submission)
            ->causedBy($external)
            ->withProperties([
                'field_count' => count($stagedChanges),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('submission_created');

        return $submission;
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

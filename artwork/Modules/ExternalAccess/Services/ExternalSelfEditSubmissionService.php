<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\Crm\Models\CrmPropertyValue;
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
     * - Staged fields (source entity) go to a new pending submission (old pending superseded).
     * - Direct fields (CRM properties) are written immediately.
     * - Returns the created pending submission, or null when only direct writes happened.
     *
     * @param array<string, array<string, mixed>> $submittedValues sectionKey => (fieldKey => value)
     */
    public function submit(ExternalAccess $external, array $submittedValues): ?ExternalPendingSubmission
    {
        $schema = $this->resolver->resolveFor($external);
        $isFirstSubmission = $this->isFirstSubmissionFor($external);

        $result = $this->db->transaction(function () use ($external, $schema, $submittedValues) {
            $stagedChanges = [];
            $directWrites = [];

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

                    if ($section->mode === SelfEditSectionMode::STAGED) {
                        $stagedChanges[] = $change;
                    } else {
                        $directWrites[] = $change;
                    }
                }
            }

            foreach ($directWrites as $write) {
                $this->persistDirectCrmPropertyWrite($write, $external);
            }

            $hadAnyChange = $stagedChanges !== [] || $directWrites !== [];

            $submission = null;
            if ($stagedChanges !== []) {
                $this->markExistingPendingAsSuperseded($external);
                $submission = $this->createPendingSubmission($external, $stagedChanges);
            }

            return ['submission' => $submission, 'hadAnyChange' => $hadAnyChange];
        });

        // Notifications are dispatched AFTER commit so a mail/queue failure never rolls back
        // the persisted changes.
        if ($result['submission'] !== null) {
            $this->notificationSender->notifyCrmSubmissionCreated($result['submission'], $isFirstSubmission);
        } elseif ($result['hadAnyChange']) {
            $this->notificationSender->notifyCrmDirectUpdate($external);
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

    /**
     * @param array<string, mixed> $write
     */
    private function persistDirectCrmPropertyWrite(array $write, ExternalAccess $external): void
    {
        if (!str_starts_with($write['field_key'], 'crm_property:')) {
            throw new \DomainException('Direct write only allowed for crm_property:* fields');
        }
        $propertyId = (int) substr($write['field_key'], strlen('crm_property:'));

        $property = CrmProperty::with('group')->findOrFail($propertyId);
        if ($property->group->is_confidential) {
            throw new \DomainException("Cannot write to confidential property {$propertyId}");
        }

        CrmPropertyValue::updateOrCreate(
            ['crm_contact_id' => $external->crm_contact_id, 'crm_property_id' => $propertyId],
            ['value' => $write['new_value']],
        );

        activity('crm_self_edit')
            ->performedOn($external->crmContact)
            ->causedBy($external)
            ->withProperties([
                'field_key' => $write['field_key'],
                'old' => $write['old_value'],
                'new' => $write['new_value'],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log('crm_property_updated');
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

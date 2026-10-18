<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\DTOs\SelfEditField;
use Artwork\Modules\ExternalAccess\DTOs\SelfEditSchema;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Enums\SelfEditSectionMode;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\Project\Services\ProjectComponentValueNormalizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExternalSelfEditSubmissionService
{
    public function __construct(
        private readonly ExternalSelfEditFieldResolver $resolver,
        private readonly DatabaseManager $db,
        private readonly ExternalNotificationSender $notificationSender,
        private readonly ExternalSelfEditFileService $fileService,
    ) {
    }

    /**
     * Processes a submit of the self-edit form.
     *
     * Every changed field (source entity columns AND CRM property values) goes into ONE new
     * pending submission (an older pending one is superseded). Nothing is written to the CRM
     * until the inviting person approves. Returns null when nothing changed.
     *
     * Upload fields carry an UploadedFile (new or replaced file) or an empty value (remove the file);
     * the file is stored provisionally with the submission (ExternalSelfEditFileService). Any other
     * value of an upload field means "unchanged" - a text path is never taken over.
     *
     * @param array<string, array<string, mixed>> $submittedValues sectionKey => (fieldKey => value)
     * @throws AuthorizationException when a file field changes while external file upload is disabled
     */
    public function submit(ExternalAccess $external, array $submittedValues): ?ExternalPendingSubmission
    {
        $schema = $this->resolver->resolveFor($external);
        $isFirstSubmission = $this->isFirstSubmissionFor($external);
        $storedPendingPaths = [];

        try {
            // Referenz: auch bei einer Exception sollen die bis dahin gespeicherten Dateien bekannt sein
            $result = $this->db->transaction(function () use (
                $external,
                $schema,
                $submittedValues,
                &$storedPendingPaths,
            ): array {
                return $this->stageSubmission($external, $schema, $submittedValues, $storedPendingPaths);
            });
        } catch (\Throwable $exception) {
            // Nichts eingereicht: vorläufig gespeicherte Dateien wieder entfernen
            foreach ($storedPendingPaths as $path) {
                Storage::disk(ExternalSelfEditFileService::DISK)->delete($path);
            }

            throw $exception;
        }

        // Vorläufige Dateien ersetzter Einreichungen erst nach dem Commit löschen
        $this->fileService->deletePendingOf($result['superseded_changes']);

        // Notifications are dispatched AFTER commit so a mail/queue failure never rolls back
        // the persisted changes.
        if ($result['submission'] !== null) {
            $this->notificationSender->notifyCrmSubmissionCreated($result['submission'], $isFirstSubmission);
        }

        return $result['submission'];
    }

    /**
     * @param array<string, array<string, mixed>> $submittedValues
     * @param list<string> $storedPendingPaths receives the provisionally stored files (cleanup on failure)
     * @return array{submission: ?ExternalPendingSubmission, superseded_changes: list<ExternalPendingFieldChange>}
     * @throws ValidationException
     * @throws AuthorizationException
     */
    private function stageSubmission(
        ExternalAccess $external,
        SelfEditSchema $schema,
        array $submittedValues,
        array &$storedPendingPaths,
    ): array {
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

                if ($section->mode !== SelfEditSectionMode::STAGED) {
                    // Defense in depth: seit der Freigabe-Vereinheitlichung gibt es keine
                    // Direktschreib-Sektionen mehr; ein solcher Schema-Eintrag wäre ein Fehler.
                    throw new \DomainException("Section {$section->key} is not staged");
                }

                $errorKey = "values.{$section->key}.{$field->key}";
                $submittedValue = $submittedValues[$section->key][$field->key];

                $change = $field->inputType === 'file'
                    ? $this->stageFileChange($field, $submittedValue, $errorKey, $storedPendingPaths)
                    : $this->stageValueChange($field, $submittedValue, $errorKey);

                if ($change === null) {
                    continue; // unchanged
                }

                $stagedChanges[] = [
                    'target_type' => $section->targetType,
                    'target_id' => $section->targetId,
                    'field_key' => $field->key,
                    'old_value' => $field->value,
                    'new_value' => $change['new_value'],
                ];
            }
        }

        if ($stagedChanges === []) {
            return ['submission' => null, 'superseded_changes' => []];
        }

        $supersededChanges = $this->markExistingPendingAsSuperseded($external);

        return [
            'submission' => $this->createPendingSubmission($external, $stagedChanges),
            'superseded_changes' => $supersededChanges,
        ];
    }

    /**
     * @return array{new_value: mixed}|null null when unchanged
     * @throws ValidationException
     */
    private function stageValueChange(SelfEditField $field, mixed $submittedValue, string $errorKey): ?array
    {
        $newValue = $this->normalizeForInputType($field, $submittedValue);

        if ($field->required && ($newValue === null || $newValue === '')) {
            throw ValidationException::withMessages([$errorKey => __('This field is required.')]);
        }

        if (
            $this->normalizeValue($newValue)
            === $this->normalizeValue($this->normalizeForInputType($field, $field->value))
        ) {
            return null;
        }

        $this->validateForInputType($field, $newValue, $errorKey);

        return ['new_value' => $newValue];
    }

    /**
     * Datei-Feld: neue Datei = vorläufig speichern, leerer Wert = Datei entfernen, sonst unverändert.
     *
     * @param list<string> $storedPendingPaths
     * @return array{new_value: array<string, string>}|null null when unchanged
     * @throws ValidationException
     * @throws AuthorizationException
     */
    private function stageFileChange(
        SelfEditField $field,
        mixed $submittedValue,
        string $errorKey,
        array &$storedPendingPaths,
    ): ?array {
        if ($submittedValue instanceof UploadedFile) {
            $this->fileService->assertUploadEnabled();
            $newValue = $this->fileService->storePending($submittedValue, $errorKey, $field->label);
            $storedPendingPaths[] = $newValue['path'];

            return ['new_value' => $newValue];
        }

        $isRemoval = $submittedValue === null || $submittedValue === '';
        if (!$isRemoval || $field->value === null || $field->value === '') {
            return null;
        }

        $this->fileService->assertUploadEnabled();

        if ($field->required) {
            throw ValidationException::withMessages([$errorKey => __('This field is required.')]);
        }

        return ['new_value' => $this->fileService->removal()];
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
     * @return list<ExternalPendingFieldChange> still open changes of the superseded submissions
     */
    private function markExistingPendingAsSuperseded(ExternalAccess $external): array
    {
        $superseded = ExternalPendingSubmission::query()
            ->where('external_access_id', $external->id)
            ->where('context', ExternalSubmissionContext::CRM_SELF)
            ->where('status', ExternalSubmissionStatus::PENDING)
            ->with(['fieldChanges' => fn ($changes) => $changes->where(
                'approval_status',
                FieldApprovalStatus::PENDING,
            )])
            ->get();

        $openChanges = [];
        foreach ($superseded as $submission) {
            foreach ($submission->fieldChanges as $change) {
                $openChanges[] = $change;
            }

            $submission->update(['status' => ExternalSubmissionStatus::SUPERSEDED]);

            activity('crm_self_edit')
                ->performedOn($submission)
                ->causedBy($external)
                ->log('submission_superseded');
        }

        return $openChanges;
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

    /**
     * Gleiche Speicherform wie intern: Checkboxen als '1'/'0' (CrmPropertyValueInput).
     */
    private function normalizeForInputType(SelfEditField $field, mixed $value): mixed
    {
        if ($field->inputType !== 'checkbox') {
            return $value;
        }

        if ($value === null || $value === '') {
            return '0';
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
    }

    /**
     * Nur geänderte Werte werden geprüft: ein inzwischen aus der Auswahl entfernter Altwert
     * blockiert das Absenden anderer Felder nicht.
     *
     * @throws ValidationException
     */
    private function validateForInputType(SelfEditField $field, mixed $value, string $errorKey): void
    {
        if ($value === null || $value === '') {
            return;
        }

        // Link-Ziele: wie bei Tab-Links keine ausführbaren Schemata (javascript:, data: …)
        if ($field->inputType === 'url' && ProjectComponentValueNormalizer::isDangerousLinkTarget((string) $value)) {
            throw ValidationException::withMessages([
                $errorKey => __('validation.url', ['attribute' => $field->label]),
            ]);
        }

        $rules = match ($field->inputType) {
            'select' => [Rule::in($field->options)],
            'number' => ['numeric'],
            'date' => ['date_format:Y-m-d'],
            'email' => ['email'],
            default => [],
        };

        if ($rules === []) {
            return;
        }

        $validator = Validator::make(
            ['value' => $value],
            ['value' => $rules],
            [],
            ['value' => $field->label],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([$errorKey => $validator->errors()->first('value')]);
        }
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}

<?php

namespace App\Http\Controllers;

use Artwork\Core\FileHandling\Download\PrivateFileResponse;
use Artwork\Modules\Crm\Enums\CrmPropertyTypeEnum;
use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Exceptions\EmailAlreadyInUseException;
use Artwork\Modules\ExternalAccess\Exceptions\NotAuthorizedToReviewException;
use Artwork\Modules\ExternalAccess\Exceptions\SubmissionAlreadyDecidedException;
use Artwork\Modules\ExternalAccess\Http\Requests\PartialDecisionsRequest;
use Artwork\Modules\ExternalAccess\Http\Requests\RejectSubmissionRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditFileService;
use Artwork\Modules\ExternalAccess\Services\ExternalSubmissionApprovalService;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExternalSubmissionReviewController extends Controller
{
    public function __construct(
        private readonly ExternalSubmissionApprovalService $approvalService,
        private readonly ExternalSelfEditFileService $fileService,
    ) {
    }

    public function index(CrmContact $contact): RedirectResponse
    {
        $pending = $contact->externalAccess
            ?->pendingSubmissions()
            ->where('status', \Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus::PENDING)
            ->latest('submitted_at')
            ->first();

        abort_if($pending === null, 404);
        $this->authorizeReview($pending);

        return redirect()->route('crm.contacts.external-submissions.show', [$contact, $pending]);
    }

    public function show(CrmContact $contact, ExternalPendingSubmission $submission): Response
    {
        $this->guardSubmissionBelongsToContact($contact, $submission);
        $this->authorizeReview($submission);

        $submission->load('fieldChanges', 'externalAccess.crmContact');

        $propertiesById = CrmProperty::query()
            ->whereIn('id', $submission->fieldChanges
                ->map(fn (ExternalPendingFieldChange $c) => $this->crmPropertyIdOf($c))
                ->filter()
                ->all())
            ->get()
            ->keyBy('id');

        return Inertia::render('CRM/ExternalSubmissionReview', [
            'contact' => [
                'id' => $contact->id,
                'display_name' => $contact->display_name,
            ],
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status->value,
                'submitted_at' => $submission->submitted_at->toIso8601String(),
                'rejection_reason' => $submission->rejection_reason,
                'external_access' => [
                    'email' => $submission->externalAccess->email,
                    'display_name' => $submission->externalAccess->crmContact?->display_name,
                ],
                'field_changes' => $submission->fieldChanges->map(function (ExternalPendingFieldChange $c) use (
                    $contact,
                    $submission,
                    $propertiesById,
                ): array {
                    $property = $propertiesById->get($this->crmPropertyIdOf($c));

                    return [
                        'id' => $c->id,
                        'field_key' => $c->field_key,
                        'field_label' => $this->resolveFieldLabel($c, $property),
                        // Typ der CRM-Eigenschaft (Checkbox-Werte '1'/'0' zeigt die Seite als Ja/Nein)
                        'field_type' => $property?->type?->value,
                        'target_type' => class_basename($c->target_type),
                        'approval_status' => $c->approval_status->value,
                        ...$this->valuesPayload($contact, $submission, $c, $property),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    /**
     * Vorgeschlagene Datei einer Upload-Änderung ansehen/herunterladen (gleiches Recht wie die Prüfseite).
     */
    public function proposedFile(
        Request $request,
        CrmContact $contact,
        ExternalPendingSubmission $submission,
        ExternalPendingFieldChange $fieldChange,
    ): StreamedResponse {
        $this->guardSubmissionBelongsToContact($contact, $submission);
        $this->authorizeReview($submission);
        abort_unless((int) $fieldChange->submission_id === (int) $submission->id, 404);

        $path = $this->fileService->existingPendingPathOf($fieldChange->new_value);
        abort_if($path === null, 404);

        $name = $this->fileService->parse($fieldChange->new_value)['name'] ?? basename($path);

        return PrivateFileResponse::make($path, $name, $request->boolean('inline'));
    }

    public function approveAll(CrmContact $contact, ExternalPendingSubmission $submission): RedirectResponse
    {
        $this->guardSubmissionBelongsToContact($contact, $submission);

        return $this->run(
            fn () => $this->approvalService->approveAll($submission, auth()->user()),
            $contact,
            $submission,
            __('Submission approved.'),
        );
    }

    public function rejectAll(
        CrmContact $contact,
        ExternalPendingSubmission $submission,
        RejectSubmissionRequest $request,
    ): RedirectResponse {
        $this->guardSubmissionBelongsToContact($contact, $submission);

        return $this->run(
            fn () => $this->approvalService->rejectAll($submission, auth()->user(), $request->validated('reason')),
            $contact,
            $submission,
            __('Submission rejected.'),
        );
    }

    public function partialDecisions(
        CrmContact $contact,
        ExternalPendingSubmission $submission,
        PartialDecisionsRequest $request,
    ): RedirectResponse {
        $this->guardSubmissionBelongsToContact($contact, $submission);

        $decisions = collect($request->validated('decisions'))
            ->mapWithKeys(fn ($d) => [(int) $d['field_change_id'] => FieldApprovalStatus::from($d['decision'])])
            ->all();

        return $this->run(
            fn () => $this->approvalService->applyPartialDecisions(
                $submission,
                auth()->user(),
                $decisions,
                $request->validated('rejection_reason'),
            ),
            $contact,
            $submission,
            __('Decisions applied.'),
        );
    }

    private function run(
        callable $action,
        CrmContact $contact,
        ExternalPendingSubmission $submission,
        string $okMessage,
    ): RedirectResponse {
        try {
            $skipped = $action();
        } catch (NotAuthorizedToReviewException $e) {
            abort(403, __($e->getMessage()));
        } catch (SubmissionAlreadyDecidedException | EmailAlreadyInUseException | \DomainException $e) {
            return redirect()
                ->route('crm.contacts.external-submissions.show', [$contact, $submission])
                ->with('error', __($e->getMessage()));
        }

        if (is_int($skipped) && $skipped > 0) {
            $okMessage .= ' ' . trans_choice(
                ':count change could not be applied (legacy entry or missing file) and was skipped.'
                . '|:count changes could not be applied (legacy entries or missing files) and were skipped.',
                $skipped,
                ['count' => $skipped],
            );
        }

        return redirect()
            ->route('crm.contacts.external-submissions.show', [$contact, $submission])
            ->with('status', $okMessage);
    }

    /**
     * Werte für die Prüfseite. Upload-Änderungen zeigen Dateinamen statt Speicherpfaden; die vorgeschlagene
     * Datei ist über proposedFile() abrufbar.
     *
     * @return array{
     *     old_value: mixed,
     *     new_value: mixed,
     *     file_change: array{kind: string, name: ?string, url: ?string}|null,
     *     not_applicable_reason: ?string
     * }
     */
    private function valuesPayload(
        CrmContact $contact,
        ExternalPendingSubmission $submission,
        ExternalPendingFieldChange $change,
        ?CrmProperty $property,
    ): array {
        if ($property?->type !== CrmPropertyTypeEnum::UPLOAD) {
            return [
                'old_value' => $change->old_value,
                'new_value' => $change->new_value,
                'file_change' => null,
                'not_applicable_reason' => null,
            ];
        }

        $reason = $this->approvalService->notApplicableUploadReason($change);
        $parsed = $this->fileService->parse($change->new_value);
        $fileChange = null;
        if ($parsed !== null) {
            $hasFile = $parsed['kind'] === ExternalSelfEditFileService::KIND_UPLOAD
                && $this->fileService->existingPendingPathOf($change->new_value) !== null;
            $fileChange = [
                'kind' => $parsed['kind'],
                'name' => $parsed['name'] ?? null,
                'url' => $hasFile
                    ? route('crm.contacts.external-submissions.proposed-file', [$contact, $submission, $change])
                    : null,
            ];
        }

        return [
            // alter Wert ist der Dateiname (ExternalSelfEditFieldResolver), Altbestand ggf. ein Pfad
            'old_value' => is_string($change->old_value) && $change->old_value !== ''
                ? basename($change->old_value)
                : null,
            'new_value' => $parsed === null ? $change->new_value : ($parsed['name'] ?? null),
            'file_change' => $fileChange,
            'not_applicable_reason' => $reason,
        ];
    }

    /**
     * Nur die einladende Person oder Admins (gleiche Regel wie ExternalSubmissionApprovalService).
     */
    private function authorizeReview(ExternalPendingSubmission $submission): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $invitedById = $submission->externalAccess?->invited_by_user_id;

        abort_unless(
            $user !== null
            && (($invitedById !== null && (int) $invitedById === (int) $user->id)
                || $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)),
            403
        );
    }

    private function guardSubmissionBelongsToContact(CrmContact $contact, ExternalPendingSubmission $submission): void
    {
        if ($submission->externalAccess->crm_contact_id !== $contact->id) {
            abort(404);
        }
    }

    private function crmPropertyIdOf(ExternalPendingFieldChange $change): ?int
    {
        return str_starts_with($change->field_key, 'crm_property:')
            ? (int) substr($change->field_key, strlen('crm_property:'))
            : null;
    }

    private function resolveFieldLabel(ExternalPendingFieldChange $change, ?CrmProperty $property): string
    {
        if ($this->crmPropertyIdOf($change) !== null) {
            return $property?->name ?? $change->field_key;
        }

        // Gleiche Bezeichnungen wie die externe Maske (ExternalSelfEditFieldResolver)
        return match ($change->field_key) {
            'display_name', 'name' => __('Name'),
            'contact_person' => __('Contact person'),
            'phone' => __('Phone'),
            'website' => __('Website'),
            'address' => __('Address'),
            'position' => __('Position'),
            'first_name' => __('First name'),
            'last_name' => __('Last name'),
            'provider_name' => __('Provider name'),
            'email' => __('Email'),
            'phone_number' => __('Phone'),
            'street' => __('Street'),
            'zip_code' => __('ZIP'),
            'location' => __('City'),
            default => $change->field_key,
        };
    }
}

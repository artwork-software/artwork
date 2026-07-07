<?php

namespace App\Http\Controllers;

use Artwork\Modules\Crm\Models\CrmProperty;
use Artwork\Modules\ExternalAccess\Enums\FieldApprovalStatus;
use Artwork\Modules\ExternalAccess\Exceptions\EmailAlreadyInUseException;
use Artwork\Modules\ExternalAccess\Exceptions\NotAuthorizedToReviewException;
use Artwork\Modules\ExternalAccess\Exceptions\SubmissionAlreadyDecidedException;
use Artwork\Modules\ExternalAccess\Http\Requests\PartialDecisionsRequest;
use Artwork\Modules\ExternalAccess\Http\Requests\RejectSubmissionRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingFieldChange;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalSubmissionApprovalService;
use Artwork\Modules\Crm\Models\CrmContact;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExternalSubmissionReviewController extends Controller
{
    public function __construct(
        private readonly ExternalSubmissionApprovalService $approvalService,
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

        return redirect()->route('crm.contacts.external-submissions.show', [$contact, $pending]);
    }

    public function show(CrmContact $contact, ExternalPendingSubmission $submission): Response
    {
        $this->guardSubmissionBelongsToContact($contact, $submission);

        $submission->load('fieldChanges', 'externalAccess.crmContact');

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
                'field_changes' => $submission->fieldChanges->map(fn (ExternalPendingFieldChange $c) => [
                    'id' => $c->id,
                    'field_key' => $c->field_key,
                    'field_label' => $this->resolveFieldLabel($c),
                    'target_type' => class_basename($c->target_type),
                    'old_value' => $c->old_value,
                    'new_value' => $c->new_value,
                    'approval_status' => $c->approval_status->value,
                ])->values()->all(),
            ],
        ]);
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
            $action();
        } catch (NotAuthorizedToReviewException $e) {
            abort(403, __($e->getMessage()));
        } catch (SubmissionAlreadyDecidedException | EmailAlreadyInUseException | \DomainException $e) {
            return redirect()
                ->route('crm.contacts.external-submissions.show', [$contact, $submission])
                ->with('error', __($e->getMessage()));
        }

        return redirect()
            ->route('crm.contacts.external-submissions.show', [$contact, $submission])
            ->with('status', $okMessage);
    }

    private function guardSubmissionBelongsToContact(CrmContact $contact, ExternalPendingSubmission $submission): void
    {
        if ($submission->externalAccess->crm_contact_id !== $contact->id) {
            abort(404);
        }
    }

    private function resolveFieldLabel(ExternalPendingFieldChange $change): string
    {
        if (str_starts_with($change->field_key, 'crm_property:')) {
            $propertyId = (int) substr($change->field_key, strlen('crm_property:'));

            return CrmProperty::query()->find($propertyId)?->name ?? $change->field_key;
        }

        return match ($change->field_key) {
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

<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionContext;
use Artwork\Modules\ExternalAccess\Enums\ExternalSubmissionStatus;
use Artwork\Modules\ExternalAccess\Http\Requests\SubmitCrmSelfEditRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditFieldResolver;
use Artwork\Modules\ExternalAccess\Services\ExternalSelfEditSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExternalCrmController extends Controller
{
    public function __construct(
        private readonly ExternalSelfEditFieldResolver $resolver,
        private readonly ExternalSelfEditSubmissionService $submissionService,
    ) {
    }

    public function show(Request $request): Response
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');
        $contact = $external->crmContact;

        $groups = CrmPropertyGroup::query()
            ->where('is_confidential', false)
            ->whereHas(
                'properties.contactTypes',
                fn ($q) => $q->where('crm_contact_types.id', $contact->crm_contact_type_id),
            )
            ->with([
                'properties' => function ($q) use ($contact): void {
                    $q->whereHas(
                        'contactTypes',
                        fn ($cq) => $cq->where('crm_contact_types.id', $contact->crm_contact_type_id),
                    )
                        ->with([
                            'values' => fn ($vq) => $vq->where('crm_contact_id', $contact->id),
                        ]);
                },
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CrmPropertyGroup $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'properties' => $group->properties->map(fn ($property) => [
                    'id' => $property->id,
                    'name' => $property->name,
                    'value' => $property->values->first()?->value,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('Crm/Show', [
            'groups' => $groups,
            'submissionStatus' => $this->latestSubmissionStatusPayload($external),
        ]);
    }

    public function edit(Request $request): Response
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');
        $schema = $this->resolver->resolveFor($external);

        $currentPending = ExternalPendingSubmission::currentPending($external)
            ->with('fieldChanges')
            ->first();

        return Inertia::render('Crm/Edit', [
            'schema' => $schema->toArray(),
            'pendingSubmission' => $currentPending ? [
                'id' => $currentPending->id,
                'submitted_at' => $currentPending->submitted_at->toIso8601String(),
                'field_count' => $currentPending->fieldChanges->count(),
            ] : null,
        ]);
    }

    public function submit(SubmitCrmSelfEditRequest $request): RedirectResponse
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        $submission = $this->submissionService->submit($external, $request->validated('values'));

        return redirect()->route('external.crm.show')->with('status', $submission
            ? __('Your changes have been submitted for review.')
            : __('Your changes have been saved.'));
    }

    public function submissionStatus(Request $request): JsonResponse
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        return response()->json($this->latestSubmissionStatusPayload($external));
    }

    /**
     * @return array<string, mixed>
     */
    private function latestSubmissionStatusPayload(ExternalAccess $external): array
    {
        $latest = ExternalPendingSubmission::query()
            ->where('external_access_id', $external->id)
            ->where('context', ExternalSubmissionContext::CRM_SELF)
            ->latest('submitted_at')
            ->first();

        return [
            'has_pending' => $latest?->status === ExternalSubmissionStatus::PENDING,
            'latest_status' => $latest?->status->value,
            'latest_at' => $latest?->reviewed_at?->toIso8601String() ?? $latest?->submitted_at?->toIso8601String(),
            'rejection_reason' => $latest?->rejection_reason,
        ];
    }
}

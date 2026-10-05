<?php

namespace Artwork\Modules\ExternalAccess\Http\Controllers;

use App\Http\Controllers\Controller;
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
    /**
     * Der Submission-Service (→ NotificationService) wird bewusst NICHT im Konstruktor injiziert:
     * Laravel baut den Controller vor der Middleware-Pipeline, und die Kette würde den Session-Store
     * mit dem internen Cookie-Namen anlegen (siehe SwapExternalSessionConfig).
     */
    public function __construct(
        private readonly ExternalSelfEditFieldResolver $resolver,
    ) {
    }

    public function show(Request $request): Response
    {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        // Gleiche Quelle wie das Bearbeiten-Formular, damit Lese- und Bearbeitungsansicht nie driften.
        $schema = $this->resolver->resolveFor($external);

        $groups = array_map(static fn ($section) => [
            'id' => $section['key'],
            'name' => $section['label'],
            'properties' => array_map(static fn ($field) => [
                'id' => $field['key'],
                'name' => $field['label'],
                'value' => $field['value'],
            ], $section['fields']),
        ], $schema->toArray()['sections']);

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

    public function submit(
        SubmitCrmSelfEditRequest $request,
        ExternalSelfEditSubmissionService $submissionService,
    ): RedirectResponse {
        /** @var ExternalAccess $external */
        $external = $request->user('external');

        $submission = $submissionService->submit($external, $request->validated('values'));

        return redirect()->route('external.crm.show')->with('status', $submission
            ? __('Your changes have been submitted for review.')
            : __('No changes detected.'));
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

<?php

namespace App\Http\Controllers;

use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\ExternalAccess\Enums\ExternalAccessType;
use Artwork\Modules\ExternalAccess\Http\Requests\ExtendCrmAccessRequest;
use Artwork\Modules\ExternalAccess\Http\Requests\RelinkAccessRequest;
use Artwork\Modules\ExternalAccess\Http\Requests\RevokeAccessRequest;
use Artwork\Modules\ExternalAccess\Http\Requests\UpdateScopeRequest;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessRepository;
use Artwork\Modules\ExternalAccess\Repositories\ExternalAccessScopeRepository;
use Artwork\Modules\ExternalAccess\Services\ExternalAccessManagementService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ExternalAccessManagementController extends Controller
{
    public function __construct(
        private readonly ExternalAccessRepository $repository,
        private readonly ExternalAccessScopeRepository $scopeRepository,
        private readonly ExternalAccessManagementService $service,
    ) {
    }

    public function index(Request $request): Response
    {
        $statusFilter = $request->get('status');

        return Inertia::render('CRM/ExternalAccess/Index', [
            'accesses' => $this->repository->findGlobalIndex($statusFilter),
            'filters' => ['status' => $statusFilter],
        ]);
    }

    public function show(Request $request, ExternalAccess $access): Response
    {
        $this->authorize('view', $access);

        $access->load('crmContact:id,display_name,entity_type,entity_id', 'invitedBy:id,first_name,last_name');
        $submissions = $access->pendingSubmissions()
            ->withCount('fieldChanges')
            ->orderByDesc('submitted_at')
            ->limit(20)
            ->get();

        return Inertia::render('CRM/ExternalAccess/Show', [
            'access' => $this->serializeAccess($access),
            'scopes' => $this->scopeRepository->findByAccess($access)
                ->map(fn (ExternalAccessScope $s) => $this->serializeScope($s))->all(),
            'submissions' => $submissions->map(fn (ExternalPendingSubmission $s) => [
                'id' => $s->id,
                'status' => $s->status->value,
                'submitted_at' => $s->submitted_at->toIso8601String(),
                'field_count' => $s->field_changes_count,
            ])->all(),
            'audit_log' => $this->loadAuditLog($access),
            'can_manage' => $request->user()->can('manage', $access),
            'can_relink' => $request->user()->can('relink', $access),
        ]);
    }

    public function extendCrmAccess(ExtendCrmAccessRequest $request, ExternalAccess $access): RedirectResponse
    {
        $this->authorize('manage', $access);

        $this->service->extendCrmAccess($access, Carbon::parse($request->validated('expires_at')), $request->user());

        return redirect()->back()->with('status', __('CRM access extended.'));
    }

    public function revoke(RevokeAccessRequest $request, ExternalAccess $access): RedirectResponse
    {
        $this->authorize('manage', $access);

        $this->service->revoke($access, $request->user(), $request->validated('reason'));

        return redirect()->back()->with('status', __('Access has been revoked.'));
    }

    public function reactivate(Request $request, ExternalAccess $access): RedirectResponse
    {
        $this->authorize('manage', $access);

        $this->service->reactivate($access, $request->user());

        return redirect()->back()->with('status', __('Access has been reactivated.'));
    }

    public function updateScope(
        UpdateScopeRequest $request,
        ExternalAccess $access,
        ExternalAccessScope $scope,
    ): RedirectResponse {
        $this->authorize('manage', $access);
        $this->ensureScopeBelongsToAccess($access, $scope);

        if ($request->filled('valid_to')) {
            $this->service->extendScope($scope, Carbon::parse($request->validated('valid_to')), $request->user());
        }

        if ($request->filled('access_type')) {
            $this->service->updateScopeAccessType(
                $scope,
                ExternalAccessType::from($request->validated('access_type')),
                $request->user(),
            );
        }

        return redirect()->back()->with('status', __('Scope updated.'));
    }

    public function endScope(Request $request, ExternalAccess $access, ExternalAccessScope $scope): RedirectResponse
    {
        $this->authorize('manage', $access);
        $this->ensureScopeBelongsToAccess($access, $scope);

        $this->service->endScope($scope, $request->user());

        return redirect()->back()->with('status', __('Scope ended.'));
    }

    public function relink(RelinkAccessRequest $request, ExternalAccess $access): RedirectResponse
    {
        $this->authorize('relink', $access);

        $newContact = CrmContact::query()->findOrFail($request->validated('new_crm_contact_id'));
        $this->service->relink($access, $newContact, $request->user());

        return redirect()->back()->with('status', __('Access has been re-linked.'));
    }

    private function ensureScopeBelongsToAccess(ExternalAccess $access, ExternalAccessScope $scope): void
    {
        if ($scope->external_access_id !== $access->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAccess(ExternalAccess $access): array
    {
        return [
            'id' => $access->id,
            'email' => $access->email,
            'crm_contact_id' => $access->crm_contact_id,
            'crm_contact' => [
                'id' => $access->crmContact?->id,
                'display_name' => $access->crmContact?->display_name,
                'entity_type' => $access->crmContact?->entity_type,
            ],
            'crm_access_expires_at' => $access->crm_access_expires_at?->toIso8601String(),
            'revoked_at' => $access->revoked_at?->toIso8601String(),
            'last_login_at' => $access->last_login_at?->toIso8601String(),
            'created_at' => $access->created_at?->toIso8601String(),
            'invited_by' => $access->invitedBy ? [
                'id' => $access->invitedBy->id,
                'first_name' => $access->invitedBy->first_name,
                'last_name' => $access->invitedBy->last_name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeScope(ExternalAccessScope $scope): array
    {
        return [
            'id' => $scope->id,
            'project' => ['id' => $scope->project?->id, 'name' => $scope->project?->name],
            'tab' => ['id' => $scope->projectTab?->id, 'name' => $scope->projectTab?->name],
            'access_type' => $scope->access_type->value,
            'valid_from' => $scope->valid_from->toIso8601String(),
            'valid_to' => $scope->valid_to->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadAuditLog(ExternalAccess $access): array
    {
        $scopeIds = $access->scopes()->pluck('id');
        $submissionIds = $access->pendingSubmissions()->pluck('id');

        return Activity::query()
            ->where(function ($q) use ($access, $scopeIds, $submissionIds): void {
                $q->where(function ($q) use ($access): void {
                    $q->where('subject_type', $access->getMorphClass())->where('subject_id', $access->id);
                })->orWhere(function ($q) use ($scopeIds): void {
                    $q->where('subject_type', (new ExternalAccessScope())->getMorphClass())
                        ->whereIn('subject_id', $scopeIds);
                })->orWhere(function ($q) use ($submissionIds): void {
                    $q->where('subject_type', (new ExternalPendingSubmission())->getMorphClass())
                        ->whereIn('subject_id', $submissionIds);
                });
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->with('causer')
            ->get()
            ->map(fn (Activity $a) => [
                'event' => $a->event ?? $a->description,
                'subject_type' => class_basename((string) $a->subject_type),
                'subject_id' => $a->subject_id,
                'causer' => $this->serializeCauser($a->causer),
                'properties' => $a->properties,
                'created_at' => $a->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCauser(?Model $causer): array
    {
        if ($causer === null) {
            return ['type' => 'unknown', 'label' => __('Deleted or unknown')];
        }
        if ($causer instanceof User) {
            return [
                'type' => 'user',
                'id' => $causer->id,
                'label' => trim($causer->first_name . ' ' . $causer->last_name),
            ];
        }
        if ($causer instanceof ExternalAccess) {
            return [
                'type' => 'external',
                'id' => $causer->id,
                'label' => $causer->crmContact?->display_name ?? $causer->email,
                'is_external' => true,
            ];
        }

        return ['type' => 'unknown', 'label' => class_basename($causer)];
    }
}

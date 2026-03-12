<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Shift\Http\Requests\AssignContractsToRuleRequest;
use Artwork\Modules\Shift\Http\Requests\AssignUsersToRuleRequest;
use Artwork\Modules\Shift\Http\Requests\GetViolationsForDateRangeRequest;
use Artwork\Modules\Shift\Http\Requests\ProcessViolationRequest;
use Artwork\Modules\Shift\Http\Requests\StoreManualViolationRequest;
use Artwork\Modules\Shift\Http\Requests\StoreShiftRuleRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateContractAssignmentsRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateShiftRuleRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateViolationStatusRequest;
use Artwork\Modules\Shift\Http\Requests\ValidateShiftRulesRequest;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftRuleController extends Controller
{
    public function __construct(
        private readonly ShiftRuleService $shiftRuleService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('ShiftWarnings/Index', [
            'rules' => $this->shiftRuleService->getAllWithRelations(),
            'availableRuleTypes' => $this->shiftRuleService->getAvailableRuleTypes(),
            'contracts' => UserContract::all()
        ]);
    }

    public function store(StoreShiftRuleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->shiftRuleService->createRule(
            [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'trigger_type' => $validated['trigger_type'],
                'individual_number_value' => $validated['individual_number_value'],
                'warning_color' => $validated['warning_color'],
                'is_active' => true,
            ],
            $validated['contract_ids'] ?? null,
            $validated['user_ids'] ?? null
        );

        return redirect()->back()->with('flash', [
            'message' => 'Rule successfully created'
        ]);
    }

    public function update(UpdateShiftRuleRequest $request, ShiftRule $shiftRule): RedirectResponse
    {
        $validated = $request->validated();

        $this->shiftRuleService->updateRule(
            $shiftRule,
            [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'individual_number_value' => $validated['individual_number_value'],
                'warning_color' => $validated['warning_color'],
            ],
            $validated['contract_ids'] ?? null,
            $validated['user_ids'] ?? null
        );

        return redirect()->back()->with('flash', [
            'message' => 'Rule successfully updated'
        ]);
    }

    public function destroy(ShiftRule $shiftRule): RedirectResponse
    {
        $this->shiftRuleService->deleteRule($shiftRule);

        return redirect()->back()->with('flash', [
            'message' => 'Rule successfully deleted'
        ]);
    }

    public function contractAssignments(): Response
    {
        return Inertia::render('ShiftWarnings/ContractAssignments', [
            'contracts' => UserContract::with(['shiftRules', 'userContractAssigns.user'])->get(),
            'rules' => $this->shiftRuleService->getActiveRules()
        ]);
    }

    public function updateContractAssignments(
        UpdateContractAssignmentsRequest $request,
        UserContract $contract
    ): RedirectResponse {
        $this->shiftRuleService->updateContractAssignments($contract, $request->validated()['rule_ids'] ?? []);

        return redirect()->back()->with('flash', [
            'message' => 'Rule assignments successfully updated'
        ]);
    }

    public function validateRules(ValidateShiftRulesRequest $request): Response
    {
        $validated = $request->validated();

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            if (!empty($validated['user_id'])) {
                $user = User::find($validated['user_id']);
                $violations = $this->shiftRuleService->validateRulesForUser($user, $startDate, $endDate);
            } else {
                $violations = $this->shiftRuleService->validateShiftRulesForDateRange($startDate, $endDate);
            }

            return Inertia::render('ShiftWarnings/Index', [
                'rules' => $this->shiftRuleService->getAllWithRelations(),
                'availableRuleTypes' => $this->shiftRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'violations' => $this->shiftRuleService->mapViolationsToArray($violations),
                'violationsCount' => $violations->count(),
                'dateRange' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString()
                ]
            ]);
        } catch (\Exception $e) {
            return Inertia::render('ShiftWarnings/Index', [
                'rules' => $this->shiftRuleService->getAllWithRelations(),
                'availableRuleTypes' => $this->shiftRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'error' => 'Error validating rules: ' . $e->getMessage()
            ]);
        }
    }

    public function getPendingViolations(): Response
    {
        $violations = $this->shiftRuleService->getActiveViolations();

        return Inertia::render('ShiftWarnings/Violations', [
            'violations' => $this->shiftRuleService->mapViolationsToArray($violations),
        ]);
    }

    public function updateViolationStatus(UpdateViolationStatusRequest $request, int $violationId): RedirectResponse
    {
        try {
            $this->shiftRuleService->updateViolationStatus(
                $violationId,
                $request->validated()['status'],
                auth()->id()
            );

            return redirect()->back()->with('flash', [
                'message' => 'Status successfully updated'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating status: ' . $e->getMessage());
        }
    }

    public function show(ShiftRule $shiftRule): Response
    {
        return Inertia::render('ShiftRules/Show', [
            'rule' => $shiftRule->load(['usersToNotify', 'contracts'])
        ]);
    }

    public function assignContracts(AssignContractsToRuleRequest $request, ShiftRule $shiftRule): RedirectResponse
    {
        $this->shiftRuleService->syncContractsForRule($shiftRule, $request->validated()['contract_ids']);

        return redirect()->back()->with('flash', [
            'message' => 'Contracts successfully assigned'
        ]);
    }

    public function assignUsers(AssignUsersToRuleRequest $request, ShiftRule $shiftRule): RedirectResponse
    {
        $this->shiftRuleService->syncUsersForRule($shiftRule, $request->validated()['user_ids']);

        return redirect()->back()->with('flash', [
            'message' => 'Users successfully assigned'
        ]);
    }

    public function resolveViolation(Request $request, ShiftRuleViolation $violation): RedirectResponse
    {
        $this->shiftRuleService->resolveViolation($violation, auth()->id());

        return redirect()->back()->with('flash', [
            'message' => 'Rule violation successfully resolved'
        ]);
    }

    public function ignoreViolation(Request $request, ShiftRuleViolation $violation): RedirectResponse
    {
        $this->shiftRuleService->ignoreViolation($violation, auth()->id());

        return redirect()->back()->with('flash', [
            'message' => 'Rule violation successfully ignored'
        ]);
    }

    public function storeManualViolation(StoreManualViolationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->shiftRuleService->createManualViolation([
            'user_id' => $validated['user_id'],
            'shift_rule_id' => $validated['shift_rule_id'],
            'violation_date' => $validated['violation_date'],
            'reason' => $validated['reason'] ?? null,
            'severity' => $validated['severity'] ?? 'warning',
            'status' => 'active',
            'is_manual' => true,
            'created_by_user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('flash', [
            'message' => 'Rule violation successfully created'
        ]);
    }

    public function processViolation(ProcessViolationRequest $request, ShiftRuleViolation $violation): RedirectResponse
    {
        if ($violation->status !== 'active') {
            return redirect()->back()->with('error', 'Violation is not active.');
        }

        $validated = $request->validated();

        if (round($validated['compensation_days'] * 2) !== $validated['compensation_days'] * 2) {
            return redirect()->back()->withErrors([
                'compensation_days' => 'Compensation days must be in 0.5 increments.'
            ]);
        }

        $this->shiftRuleService->processViolation($violation, [
            'compensation_days' => $validated['compensation_days'],
            'compensation_deadline' => $validated['compensation_deadline'],
            'compensation_reason' => $validated['compensation_reason'] ?? null,
        ]);

        return redirect()->back()->with('flash', [
            'message' => 'Rule violation successfully processed'
        ]);
    }

    public function grantCompensation(ShiftRuleViolation $violation): RedirectResponse
    {
        if (!$violation->hasCompensation()) {
            return redirect()->back()->with('error', 'No compensation days assigned.');
        }

        if ($violation->compensation_granted_at !== null) {
            return redirect()->back()->with('error', 'Compensation already granted.');
        }

        $this->shiftRuleService->grantCompensation($violation, auth()->id());

        return redirect()->back()->with('flash', [
            'message' => 'Compensation successfully granted'
        ]);
    }

    public function getViolationsForDateRange(GetViolationsForDateRangeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $violations = $this->shiftRuleService->getViolationsForDateRange(
            $validated['start_date'],
            $validated['end_date'],
            $validated['user_ids'] ?? null
        );

        return new JsonResponse($violations);
    }

    public function activeRules(): JsonResponse
    {
        return new JsonResponse(
            $this->shiftRuleService->getActiveRules(['id', 'name', 'description', 'warning_color', 'trigger_type'])
        );
    }
}

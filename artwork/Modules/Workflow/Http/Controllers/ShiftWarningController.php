<?php

namespace Artwork\Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Workflow\Models\WorkflowRuleViolation;
use Artwork\Modules\Workflow\Services\WorkflowRuleService;
use Artwork\Modules\Workflow\Models\WorkflowRule;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\Workflow\Http\Requests\StoreShiftWarningRequest;
use Artwork\Modules\Workflow\Http\Requests\UpdateShiftWarningRequest;
use Artwork\Modules\Workflow\Http\Requests\UpdateContractAssignmentsRequest;
use Artwork\Modules\Workflow\Http\Requests\ValidateRulesRequest;
use Artwork\Modules\Workflow\Http\Requests\UpdateViolationStatusRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShiftWarningController extends Controller
{
    public function __construct(
        private readonly WorkflowRuleService $workflowRuleService
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('ShiftWarnings/Index', [
            'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
            'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
            'contracts' => UserContract::all()
        ]);
    }

    public function store(StoreShiftWarningRequest $request): Response
    {
        $validated = $request->validated();

        $rule = $this->workflowRuleService->createRule(
            name: $validated['name'],
            triggerType: $validated['trigger_type'],
            value: $validated['individual_number_value'],
            configuration: [
                'description' => $validated['description'] ?? '',
                'warning_color' => $validated['warning_color'],
                'notify_on_violation' => $validated['notify_on_violation'] ?? false
            ]
        );

        if (!empty($validated['contract_ids'])) {
            $rule->contracts()->sync($validated['contract_ids']);
        }

        if (!empty($validated['user_ids'])) {
            $rule->usersToNotify()->sync($validated['user_ids']);
        }

        return Inertia::render('ShiftWarnings/Index', [
            'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
            'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
            'contracts' => UserContract::all(),
            'flash' => [
                'message' => 'Regel erfolgreich erstellt'
            ]
        ]);
    }

    public function update(UpdateShiftWarningRequest $request, WorkflowRule $rule): Response
    {
        $validated = $request->validated();

        $rule->update([
            'name' => $validated['name'],
            'individual_number_value' => $validated['individual_number_value'],
            'warning_color' => $validated['warning_color'],
            'notify_on_violation' => $validated['notify_on_violation'] ?? false,
            'configuration' => array_merge($rule->configuration ?? [], [
                'description' => $validated['description'] ?? ''
            ])
        ]);

        // Aktualisiere Zuweisungen
        $rule->contracts()->sync($validated['contract_ids'] ?? []);
        $rule->usersToNotify()->sync($validated['user_ids'] ?? []);

        return Inertia::render('ShiftWarnings/Index', [
            'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
            'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
            'contracts' => UserContract::all(),
            'flash' => [
                'message' => 'Regel erfolgreich aktualisiert'
            ]
        ]);
    }

    public function destroy(WorkflowRule $rule): Response
    {
        $rule->delete();

        return Inertia::render('ShiftWarnings/Index', [
            'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
            'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
            'contracts' => UserContract::all(),
            'flash' => [
                'message' => 'Regel erfolgreich gelöscht'
            ]
        ]);
    }

    public function contractAssignments(): Response
    {
        return Inertia::render('ShiftWarnings/ContractAssignments', [
            'contracts' => UserContract::with(['workflowRules', 'userContractAssigns.user'])->get(),
            'rules' => WorkflowRule::where('is_active', true)->get()
        ]);
    }

    public function updateContractAssignments(
        UpdateContractAssignmentsRequest $request,
        UserContract $contract
    ): Response {
        $validated = $request->validated();

        $contract->workflowRules()->sync($validated['rule_ids'] ?? []);

        return Inertia::render('ShiftWarnings/ContractAssignments', [
            'contracts' => UserContract::with(['workflowRules', 'userContractAssigns.user'])->get(),
            'rules' => WorkflowRule::where('is_active', true)->get(),
            'flash' => [
                'message' => 'Regelzuweisungen erfolgreich aktualisiert'
            ]
        ]);
    }

    public function getRuleConfiguration(string $ruleType): Response
    {
        $configuration = $this->workflowRuleService->getRuleConfiguration($ruleType);

        return Inertia::render('ShiftWarnings/Index', [
            'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
            'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
            'contracts' => UserContract::all(),
            'ruleConfiguration' => $configuration
        ]);
    }

    public function validateRules(ValidateRulesRequest $request): Response
    {
        $validated = $request->validated();

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            if (!empty($validated['user_id'])) {
                $user = User::find($validated['user_id']);
                $violations = $this->workflowRuleService->getViolationsForSubject($user, $startDate, $endDate);
            } else {
                $violations = $this->workflowRuleService->checkRuleViolationsForDateRange($startDate, $endDate);
            }

            $violationsData = $violations->map(function ($violation) {
                return [
                    'id' => $violation->id,
                    'rule_name' => $violation->workflowRule?->name,
                    'violation_date' => $violation->violation_date,
                    'message' => $violation->violation_data['message'] ?? 'Regelverstoß erkannt',
                    'severity' => $violation->severity,
                    'warning_color' => $violation->workflowRule?->warning_color,
                    'violation_data' => $violation->violation_data
                ];
            })->values();

            return Inertia::render('ShiftWarnings/Index', [
                'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'violations' => $violationsData,
                'violationsCount' => $violations->count(),
                'dateRange' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString()
                ]
            ]);
        } catch (\Exception $e) {
            return Inertia::render('ShiftWarnings/Index', [
                'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'error' => 'Fehler beim Validieren der Regeln: ' . $e->getMessage()
            ]);
        }
    }

    public function getPendingViolations(): Response
    {
        try {
            $violations = $this->workflowRuleService->getPendingViolations();

            $violationsData = $violations->map(function ($violation) {
                return [
                    'id' => $violation->id,
                    'rule_name' => $violation->workflowRule?->name,
                    'violation_date' => $violation->violation_date,
                    'message' => $violation->violation_data['message'] ?? 'Regelverstoß erkannt',
                    'severity' => $violation->severity,
                    'status' => $violation->status,
                    'warning_color' => $violation->workflowRule?->warning_color
                ];
            })->values();

            return Inertia::render('ShiftWarnings/Index', [
                'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'pendingViolations' => $violationsData,
                'pendingViolationsCount' => $violations->count()
            ]);
        } catch (\Exception $e) {
            return Inertia::render('ShiftWarnings/Index', [
                'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'error' => 'Fehler beim Abrufen der Verstöße: ' . $e->getMessage()
            ]);
        }
    }

    public function updateViolationStatus(UpdateViolationStatusRequest $request, int $violationId): Response
    {
        $validated = $request->validated();

        try {
            $violation = WorkflowRuleViolation::findOrFail($violationId);
            $updated = $this->workflowRuleService->updateViolationStatus($violation, $validated['status']);

            if ($updated) {
                return Inertia::render('ShiftWarnings/Index', [
                    'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
                    'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
                    'contracts' => UserContract::all(),
                    'flash' => [
                        'message' => 'Status erfolgreich aktualisiert'
                    ]
                ]);
            }

            return Inertia::render('ShiftWarnings/Index', [
                'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'error' => 'Status konnte nicht aktualisiert werden'
            ]);
        } catch (\Exception $e) {
            return Inertia::render('ShiftWarnings/Index', [
                'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'error' => 'Fehler beim Aktualisieren des Status: ' . $e->getMessage()
            ]);
        }
    }
}

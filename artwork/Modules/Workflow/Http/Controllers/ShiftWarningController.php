<?php

namespace Artwork\Modules\Workflow\Http\Controllers;

use Artwork\Modules\Workflow\Services\WorkflowRuleService;
use Artwork\Modules\Workflow\Models\WorkflowRule;
use Artwork\Modules\User\Models\UserContract;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShiftWarningController
{
    public function __construct(
        private readonly WorkflowRuleService $workflowRuleService
    ) {}

    public function index(): Response
    {
        return Inertia::render('ShiftWarnings/Index', [
            'rules' => WorkflowRule::with(['usersToNotify', 'contracts'])->get(),
            'availableRuleTypes' => $this->workflowRuleService->getAvailableRuleTypes(),
            'contracts' => UserContract::all()
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string',
            'individual_number_value' => 'required|numeric|min:0',
            'warning_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'notify_on_violation' => 'boolean',
            'contract_ids' => 'array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id'
        ]);

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

        // Zuweisungen zu Verträgen
        if (!empty($validated['contract_ids'])) {
            $rule->contracts()->sync($validated['contract_ids']);
        }

        // Zuweisungen zu Benutzern für Benachrichtigungen
        if (!empty($validated['user_ids'])) {
            $rule->usersToNotify()->sync($validated['user_ids']);
        }

        return response()->json([
            'message' => 'Regel erfolgreich erstellt',
            'rule' => $rule->load(['contracts', 'usersToNotify'])
        ]);
    }

    public function update(Request $request, WorkflowRule $rule): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'individual_number_value' => 'required|numeric|min:0',
            'warning_color' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'notify_on_violation' => 'boolean',
            'contract_ids' => 'array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'array',
            'user_ids.*' => 'exists:users,id'
        ]);

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

        return response()->json([
            'message' => 'Regel erfolgreich aktualisiert',
            'rule' => $rule->load(['contracts', 'usersToNotify'])
        ]);
    }

    public function destroy(WorkflowRule $rule): JsonResponse
    {
        $rule->delete();

        return response()->json([
            'message' => 'Regel erfolgreich gelöscht'
        ]);
    }

    public function contractAssignments(): Response
    {
        return Inertia::render('ShiftWarnings/ContractAssignments', [
            'contracts' => UserContract::with(['workflowRules', 'userContractAssigns.user'])->get(),
            'rules' => WorkflowRule::where('is_active', true)->get()
        ]);
    }

    public function updateContractAssignments(Request $request, UserContract $contract): JsonResponse
    {
        $validated = $request->validate([
            'rule_ids' => 'array',
            'rule_ids.*' => 'exists:workflow_rules,id'
        ]);

        $contract->workflowRules()->sync($validated['rule_ids'] ?? []);

        return response()->json([
            'message' => 'Regelzuweisungen erfolgreich aktualisiert',
            'contract' => $contract->load('workflowRules')
        ]);
    }

    public function getRuleConfiguration(string $ruleType): JsonResponse
    {
        $configuration = $this->workflowRuleService->getRuleConfiguration($ruleType);

        return response()->json($configuration);
    }
}
<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            'rules' => ShiftRule::with(['usersToNotify', 'contracts'])->get(),
            'availableRuleTypes' => $this->getAvailableRuleTypes(),
            'contracts' => UserContract::all()
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|string|in:maxWorkingHoursOnDay,maxConsecWorkingDays,maxWorkingHoursOnWeek,restTimeBeforeWorkday,restTimeBeforeHoliday',
            'individual_number_value' => 'required|numeric|min:0.1',
            'warning_color' => 'required|string',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $rule = ShiftRule::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'trigger_type' => $validated['trigger_type'],
            'individual_number_value' => $validated['individual_number_value'],
            'warning_color' => $validated['warning_color'],
            'is_active' => true
        ]);

        if (!empty($validated['contract_ids'])) {
            $rule->contracts()->sync($validated['contract_ids']);
        }

        if (!empty($validated['user_ids'])) {
            $rule->usersToNotify()->sync($validated['user_ids']);
        }

        return redirect()->back()->with('flash', [
            'message' => 'Regel erfolgreich erstellt'
        ]);
    }

    public function update(Request $request, ShiftRule $shiftRule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'individual_number_value' => 'required|numeric|min:0.1',
            'warning_color' => 'required|string',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'exists:user_contracts,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $shiftRule->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'individual_number_value' => $validated['individual_number_value'],
            'warning_color' => $validated['warning_color']
        ]);

        // Update assignments
        $shiftRule->contracts()->sync($validated['contract_ids'] ?? []);
        $shiftRule->usersToNotify()->sync($validated['user_ids'] ?? []);

        return redirect()->back()->with('flash', [
            'message' => 'Regel erfolgreich aktualisiert'
        ]);
    }

    public function destroy(ShiftRule $shiftRule): RedirectResponse
    {
        $shiftRule->delete();

        return redirect()->back()->with('flash', [
            'message' => 'Regel erfolgreich gelöscht'
        ]);
    }

    public function contractAssignments(): Response
    {
        return Inertia::render('ShiftWarnings/ContractAssignments', [
            'contracts' => UserContract::with(['shiftRules', 'userContractAssigns.user'])->get(),
            'rules' => ShiftRule::where('is_active', true)->get()
        ]);
    }

    public function updateContractAssignments(Request $request, UserContract $contract): RedirectResponse
    {
        $validated = $request->validate([
            'rule_ids' => 'nullable|array',
            'rule_ids.*' => 'exists:shift_rules,id'
        ]);

        $contract->shiftRules()->sync($validated['rule_ids'] ?? []);

        return redirect()->back()->with('flash', [
            'message' => 'Regelzuweisungen erfolgreich aktualisiert'
        ]);
    }

    public function validateRules(Request $request): Response
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id'
        ]);

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            if (!empty($validated['user_id'])) {
                $user = User::find($validated['user_id']);
                $violations = $this->shiftRuleService->validateRulesForUser($user, $startDate, $endDate);
            } else {
                $violations = $this->shiftRuleService->validateShiftRulesForDateRange($startDate, $endDate);
            }

            $violationsData = $violations->map(function ($violation) {
                return [
                    'id' => $violation->id,
                    'rule_name' => $violation->shiftRule?->name,
                    'user_name' => $violation->user->first_name . ' ' . $violation->user->last_name,
                    'violation_date' => $violation->violation_date,
                    'message' => $violation->getViolationMessage(),
                    'severity' => $violation->severity,
                    'warning_color' => $violation->getWarningColor(),
                    'violation_data' => $violation->violation_data
                ];
            })->values();

            return Inertia::render('ShiftWarnings/Index', [
                'rules' => ShiftRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->getAvailableRuleTypes(),
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
                'rules' => ShiftRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'error' => 'Fehler beim Validieren der Regeln: ' . $e->getMessage()
            ]);
        }
    }

    public function getPendingViolations(): Response
    {
        try {
            $violations = ShiftRuleViolation::with(['shiftRule', 'user', 'shift'])
                ->where('status', 'active')
                ->orderBy('violation_date', 'desc')
                ->get();

            $violationsData = $violations->map(function ($violation) {
                return [
                    'id' => $violation->id,
                    'rule_name' => $violation->shiftRule?->name,
                    'user_name' => $violation->user->first_name . ' ' . $violation->user->last_name,
                    'violation_date' => $violation->violation_date,
                    'message' => $violation->getViolationMessage(),
                    'severity' => $violation->severity,
                    'status' => $violation->status,
                    'warning_color' => $violation->getWarningColor()
                ];
            })->values();

            return Inertia::render('ShiftWarnings/Index', [
                'rules' => ShiftRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'pendingViolations' => $violationsData,
                'pendingViolationsCount' => $violations->count()
            ]);
        } catch (\Exception $e) {
            return Inertia::render('ShiftWarnings/Index', [
                'rules' => ShiftRule::with(['usersToNotify', 'contracts'])->get(),
                'availableRuleTypes' => $this->getAvailableRuleTypes(),
                'contracts' => UserContract::all(),
                'error' => 'Fehler beim Abrufen der Verstöße: ' . $e->getMessage()
            ]);
        }
    }

    public function updateViolationStatus(Request $request, int $violationId): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:resolved,ignored'
        ]);

        try {
            $violation = ShiftRuleViolation::findOrFail($violationId);

            if ($validated['status'] === 'resolved') {
                $violation->resolve(auth()->id());
            } else {
                $violation->ignore(auth()->id());
            }

            return redirect()->back()->with('flash', [
                'message' => 'Status erfolgreich aktualisiert'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Fehler beim Aktualisieren des Status: ' . $e->getMessage());
        }
    }

    public function show(ShiftRule $shiftRule): Response
    {
        return Inertia::render('ShiftRules/Show', [
            'rule' => $shiftRule->load(['usersToNotify', 'contracts'])
        ]);
    }

    public function assignContracts(Request $request, ShiftRule $shiftRule): RedirectResponse
    {
        $validated = $request->validate([
            'contract_ids' => 'required|array',
            'contract_ids.*' => 'exists:user_contracts,id'
        ]);

        $shiftRule->contracts()->sync($validated['contract_ids']);

        return redirect()->back()->with('flash', [
            'message' => 'Verträge erfolgreich zugewiesen'
        ]);
    }

    public function assignUsers(Request $request, ShiftRule $shiftRule): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $shiftRule->usersToNotify()->sync($validated['user_ids']);

        return redirect()->back()->with('flash', [
            'message' => 'Benutzer erfolgreich zugewiesen'
        ]);
    }

    public function resolveViolation(Request $request, ShiftRuleViolation $violation): RedirectResponse
    {
        $violation->resolve(auth()->id());

        return redirect()->back()->with('flash', [
            'message' => 'Regelverstoß erfolgreich gelöst'
        ]);
    }

    public function ignoreViolation(Request $request, ShiftRuleViolation $violation): RedirectResponse
    {
        $violation->ignore(auth()->id());

        return redirect()->back()->with('flash', [
            'message' => 'Regelverstoß erfolgreich ignoriert'
        ]);
    }

    private function getAvailableRuleTypes(): array
    {
        return [
            'maxWorkingHoursOnDay' => [
                'name' => 'Tagesmaximum an Stunden',
                'description' => 'Maximal erlaubte Arbeitsstunden pro Tag'
            ],
            'maxConsecWorkingDays' => [
                'name' => 'Maximale Tage in Folge arbeiten',
                'description' => 'Maximale aufeinanderfolgende Arbeitstage'
            ],
            'maxWorkingHoursOnWeek' => [
                'name' => 'Wochenmaximum an Stunden',
                'description' => 'Maximal erlaubte Arbeitsstunden pro Woche'
            ],
            'restTimeBeforeWorkday' => [
                'name' => 'Ruhezeit vor Werktag',
                'description' => 'Mindest-Ruhezeit zwischen zwei Arbeitstagen'
            ],
            'restTimeBeforeHoliday' => [
                'name' => 'Ruhezeit vor Sonder-/Sonntag',
                'description' => 'Mindest-Ruhezeit vor Feiertagen und Sonntagen'
            ]
        ];
    }
}

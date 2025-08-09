<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use Artwork\Core\Http\Controller;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftRuleController extends Controller
{
    public function __construct(
        private readonly ShiftRuleService $shiftRuleService
    ) {}

    public function index(): Response
    {
        return Inertia::render('ShiftRules/Index', [
            'shiftRules' => ShiftRule::with(['contracts', 'usersToNotify'])->get(),
            'triggerTypes' => [
                'maxWorkingHoursOnDay' => 'Tagesmaximum an Stunden',
                'maxConsecWorkingDays' => 'Maximale Tage in Folge arbeiten',
                'weeklyMaxHours' => 'Wochenmaximum an Stunden',
                'restTimeBeforeWorkday' => 'Ruhezeit vor Werktag',
                'restTimeBeforeHoliday' => 'Ruhezeit vor Sonder-/Sonntag',
                'minDaysBeforeCommit' => 'Mindesttage bis zur Verbindlich-Schaltung'
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:maxWorkingHoursOnDay,maxConsecWorkingDays,weeklyMaxHours,restTimeBeforeWorkday,restTimeBeforeHoliday,minDaysBeforeCommit',
            'individual_number_value' => 'required|numeric|min:0',
            'warning_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'is_active' => 'boolean'
        ]);

        $shiftRule = ShiftRule::create($validated);

        return response()->json([
            'message' => 'Shift rule created successfully',
            'shiftRule' => $shiftRule->load(['contracts', 'usersToNotify'])
        ]);
    }

    public function show(ShiftRule $shiftRule): JsonResponse
    {
        return response()->json([
            'shiftRule' => $shiftRule->load(['contracts', 'usersToNotify', 'shiftRuleViolations'])
        ]);
    }

    public function update(Request $request, ShiftRule $shiftRule): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:maxWorkingHoursOnDay,maxConsecWorkingDays,weeklyMaxHours,restTimeBeforeWorkday,restTimeBeforeHoliday,minDaysBeforeCommit',
            'individual_number_value' => 'required|numeric|min:0',
            'warning_color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
            'is_active' => 'boolean'
        ]);

        $shiftRule->update($validated);

        return response()->json([
            'message' => 'Shift rule updated successfully',
            'shiftRule' => $shiftRule->load(['contracts', 'usersToNotify'])
        ]);
    }

    public function destroy(ShiftRule $shiftRule): JsonResponse
    {
        $shiftRule->delete();

        return response()->json([
            'message' => 'Shift rule deleted successfully'
        ]);
    }

    public function assignContracts(Request $request, ShiftRule $shiftRule): JsonResponse
    {
        $validated = $request->validate([
            'contract_ids' => 'required|array',
            'contract_ids.*' => 'exists:user_contracts,id'
        ]);

        $shiftRule->contracts()->sync($validated['contract_ids']);

        return response()->json([
            'message' => 'Contracts assigned successfully',
            'shiftRule' => $shiftRule->load(['contracts', 'usersToNotify'])
        ]);
    }

    public function assignUsers(Request $request, ShiftRule $shiftRule): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $shiftRule->usersToNotify()->sync($validated['user_ids']);

        return response()->json([
            'message' => 'Users assigned successfully',
            'shiftRule' => $shiftRule->load(['contracts', 'usersToNotify'])
        ]);
    }

    public function validateRules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $violations = $this->shiftRuleService->validateShiftRulesForDateRange($startDate, $endDate);

        return response()->json([
            'message' => 'Rule validation completed',
            'violations' => $violations
        ]);
    }

    public function contractAssignments(): Response
    {
        return Inertia::render('ShiftRules/ContractAssignments', [
            'contracts' => UserContract::with(['shiftRules', 'user'])->where('is_active', true)->get(),
            'shiftRules' => ShiftRule::where('is_active', true)->get()
        ]);
    }

    public function resolveViolation(ShiftRuleViolation $violation): JsonResponse
    {
        $violation->resolve(auth()->id());

        return response()->json([
            'message' => 'Violation resolved successfully'
        ]);
    }

    public function ignoreViolation(ShiftRuleViolation $violation): JsonResponse
    {
        $violation->ignore(auth()->id());

        return response()->json([
            'message' => 'Violation ignored successfully'
        ]);
    }
}

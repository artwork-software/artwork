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
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
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

    public function compensationDashboard(Request $request): Response
    {
        $compensationDayOffRepository = app(CompensationDayOffRepository::class);

        $craftId = $request->integer('craft_id') ?: null;

        $recentActivity = \Spatie\Activitylog\Models\Activity::query()
            ->whereIn('log_name', ['compensation_day_off', 'shift_rule_violation'])
            ->with('causer')
            ->latest()
            ->paginate(15)
            ->through(fn ($a) => [
                'id' => $a->id,
                'description' => $a->description,
                'event' => $a->event,
                'log_name' => $a->log_name,
                'properties' => $a->properties,
                'causer' => $a->causer ? [
                    'first_name' => $a->causer->first_name,
                    'last_name' => $a->causer->last_name,
                ] : null,
                'created_at' => $a->created_at->toIso8601String(),
            ]);

        $crafts = \Artwork\Modules\Craft\Models\Craft::select('id', 'name', 'abbreviation', 'color')
            ->orderBy('name')
            ->get();

        return Inertia::render('CompensationDays/Index', [
            'openCompensations' => $compensationDayOffRepository->getAllOpen($craftId),
            'grantedCompensations' => $compensationDayOffRepository->getAllGranted($craftId),
            'overdueCompensations' => $compensationDayOffRepository->getAllOverdue($craftId),
            'stats' => $compensationDayOffRepository->getDashboardStats($craftId),
            'recentActivity' => $recentActivity,
            'crafts' => $crafts,
            'selectedCraftId' => $craftId,
        ]);
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
                'default_compensation_days' => $validated['default_compensation_days'] ?? null,
                'default_compensation_deadline_days' => $validated['default_compensation_deadline_days'] ?? null,
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
                'default_compensation_days' => $validated['default_compensation_days'] ?? null,
                'default_compensation_deadline_days' => $validated['default_compensation_deadline_days'] ?? null,
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
        $validated = $request->validate([
            'ignore_reason' => 'required|string|max:500',
        ]);

        $this->shiftRuleService->ignoreViolation($violation, auth()->id(), $validated['ignore_reason']);

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

        $days = (float) $validated['compensation_days'];
        if (round($days * 2) !== (float) ($days * 2)) {
            return redirect()->back()->withErrors([
                'compensation_days' => 'Compensation days must be in 0.5 increments.',
            ]);
        }

        $this->shiftRuleService->processViolation($violation, [
            'compensation_days' => $validated['compensation_days'],
            'compensation_deadline' => $validated['compensation_deadline'],
            'compensation_reason' => $validated['compensation_reason'] ?? null,
            'for_holiday' => $validated['for_holiday'] ?? false,
        ], auth()->id());

        return redirect()->back()->with('flash', [
            'message' => 'Rule violation successfully processed'
        ]);
    }

    public function grantCompensationDay(Request $request, CompensationDayOff $compensationDayOff): JsonResponse|RedirectResponse
    {
        if ($compensationDayOff->isGranted()) {
            return new JsonResponse(['error' => 'Compensation day already granted.'], 422);
        }

        $validated = $request->validate([
            'granted_date' => 'required|date',
            'remove_shifts' => 'boolean',
            'check_only' => 'boolean',
        ]);

        $grantedDate = $validated['granted_date'];

        // Check if user has shifts on that date
        $shiftsOnDate = \Artwork\Modules\Shift\Models\ShiftUser::where('user_id', $compensationDayOff->user_id)
            ->whereHas('shift', function ($query) use ($grantedDate): void {
                $query->whereDate('start_date', '<=', $grantedDate)
                    ->whereDate('end_date', '>=', $grantedDate);
            })
            ->count();

        if (!empty($validated['check_only'])) {
            return new JsonResponse([
                'has_shifts' => $shiftsOnDate > 0,
                'shift_count' => $shiftsOnDate,
            ]);
        }

        // Remove shifts if requested
        if (!empty($validated['remove_shifts']) && $shiftsOnDate > 0) {
            \Artwork\Modules\Shift\Models\ShiftUser::where('user_id', $compensationDayOff->user_id)
                ->whereHas('shift', function ($query) use ($grantedDate): void {
                    $query->whereDate('start_date', '<=', $grantedDate)
                        ->whereDate('end_date', '>=', $grantedDate);
                })
                ->delete();
        }

        $compensationDayOff->update([
            'granted_date' => $grantedDate,
            'granted_by' => auth()->id(),
            'granted_at' => now(),
        ]);

        // Invalidate WorkingHourCache
        app(\Artwork\Modules\User\Services\WorkingHourCacheService::class)
            ->forgetForEntity('user', $compensationDayOff->user_id);

        return redirect()->back()->with('flash', [
            'message' => 'Compensation day successfully granted'
        ]);
    }

    public function checkCompensationDay(Request $request, CompensationDayOff $compensationDayOff): JsonResponse
    {
        $validated = $request->validate([
            'granted_date' => 'required|date',
        ]);

        $shiftsOnDate = \Artwork\Modules\Shift\Models\ShiftUser::where('user_id', $compensationDayOff->user_id)
            ->whereHas('shift', function ($query) use ($validated): void {
                $query->whereDate('start_date', '<=', $validated['granted_date'])
                    ->whereDate('end_date', '>=', $validated['granted_date']);
            })
            ->count();

        return new JsonResponse([
            'has_shifts' => $shiftsOnDate > 0,
            'shift_count' => $shiftsOnDate,
        ]);
    }

    public function getOpenCompensationDays(User $user): JsonResponse
    {
        $compensationDayOffRepository = app(CompensationDayOffRepository::class);

        return new JsonResponse(
            $compensationDayOffRepository->getOpenForUser($user->id)
        );
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

    public function revokeCompensationDay(CompensationDayOff $compensationDayOff): RedirectResponse
    {
        if (!$compensationDayOff->isGranted()) {
            return redirect()->back()->with('flash', ['error' => 'Compensation day is not granted.']);
        }

        $compensationDayOff->update([
            'granted_date' => null,
            'granted_by' => null,
            'granted_at' => null,
        ]);

        app(\Artwork\Modules\User\Services\WorkingHourCacheService::class)
            ->forgetForEntity('user', $compensationDayOff->user_id);

        return redirect()->back()->with('flash', [
            'message' => 'Compensation day revoked successfully'
        ]);
    }

    public function deleteCompensationDay(Request $request, CompensationDayOff $compensationDayOff): RedirectResponse
    {
        $validated = $request->validate([
            'delete_reason' => 'required|string|max:500',
        ]);

        activity('compensation_day_off')
            ->performedOn($compensationDayOff)
            ->causedBy(auth()->user())
            ->withProperties([
                'delete_reason' => $validated['delete_reason'],
                'deleted_data' => $compensationDayOff->toArray(),
            ])
            ->event('deleted_with_reason')
            ->log('Compensation day off deleted');

        $userId = $compensationDayOff->user_id;
        $compensationDayOff->delete();

        app(\Artwork\Modules\User\Services\WorkingHourCacheService::class)
            ->forgetForEntity('user', $userId);

        return redirect()->back()->with('flash', [
            'message' => 'Compensation day successfully deleted'
        ]);
    }

    public function getUserWeekSchedule(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date']);
        $monday = $date->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->addDays(6);

        $dayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

        // Load shifts for the week
        $shiftUsers = \Artwork\Modules\Shift\Models\ShiftUser::where('user_id', $user->id)
            ->whereHas('shift', function ($query) use ($monday, $sunday): void {
                $query->whereDate('start_date', '<=', $sunday->toDateString())
                    ->whereDate('end_date', '>=', $monday->toDateString());
            })
            ->with('shift:id,start_date,end_date,start,end')
            ->get();

        // Load individual times for the week
        $individualTimes = $user->individualTimes()
            ->where('start_date', '<=', $sunday->toDateString())
            ->where('end_date', '>=', $monday->toDateString())
            ->get();

        // Load vacations for the week
        $vacations = $user->vacations()
            ->whereBetween('date', [$monday->toDateString(), $sunday->toDateString()])
            ->get()
            ->keyBy('date');

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $currentDay = $monday->copy()->addDays($i);
            $dateStr = $currentDay->toDateString();

            $dayShifts = $shiftUsers->filter(function ($su) use ($dateStr) {
                $shift = $su->shift;
                if (!$shift) return false;
                return $shift->start_date <= $dateStr && $shift->end_date >= $dateStr;
            })->map(fn ($su) => [
                'start' => $su->shift->start ?? '',
                'end' => $su->shift->end ?? '',
            ])->values();

            $dayIndividualTimes = $individualTimes->filter(function ($it) use ($dateStr) {
                return $it->start_date <= $dateStr && $it->end_date >= $dateStr;
            })->map(fn ($it) => [
                'start_time' => $it->start_time ?? '',
                'end_time' => $it->end_time ?? '',
                'title' => $it->title ?? '',
            ])->values();

            $vacation = $vacations->get($dateStr);

            $days[] = [
                'date' => $dateStr,
                'day_name' => $dayNames[$i],
                'day_short' => $currentDay->format('d.m'),
                'is_selected' => $dateStr === $date->toDateString(),
                'shifts' => $dayShifts,
                'individual_times' => $dayIndividualTimes,
                'is_free' => $dayShifts->isEmpty() && $dayIndividualTimes->isEmpty() && !$vacation,
                'vacation_type' => $vacation?->type ?? null,
            ];
        }

        return new JsonResponse([
            'calendar_week' => $monday->isoWeek(),
            'days' => $days,
        ]);
    }

    public function storeManualCompensationDay(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'value' => 'required|in:0.5,1.0',
            'deadline' => 'required|date',
            'reason' => 'nullable|string|max:500',
            'for_holiday' => 'sometimes|boolean',
        ]);

        CompensationDayOff::create([
            'user_id' => $validated['user_id'],
            'violation_id' => null,
            'value' => $validated['value'],
            'deadline' => $validated['deadline'],
            'reason' => $validated['reason'] ?? null,
            'for_holiday' => $validated['for_holiday'] ?? false,
        ]);

        return redirect()->back()->with('flash', [
            'message' => 'Compensation day created successfully'
        ]);
    }
}

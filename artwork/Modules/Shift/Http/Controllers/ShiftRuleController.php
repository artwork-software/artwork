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
            'contracts' => UserContract::all(),
            // Auswahl "Benachrichtigen" (usersToNotify) — nur Name und ID
            'users' => User::query()
                ->select(['id', 'first_name', 'last_name'])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
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
                // Regeltypen ohne Zahlenwert (Sonntag/Sondertag/HFT an Sondertag): Spalte ist NOT NULL -> 0
                'individual_number_value' => $this->numberValueFor($validated['trigger_type'], $validated),
                'warning_color' => $validated['warning_color'],
                'default_compensation_days' => $validated['default_compensation_days'] ?? null,
                'default_compensation_deadline_days' => $validated['default_compensation_deadline_days'] ?? null,
                'is_active' => true,
            ],
            $validated['contract_ids'] ?? null,
            $validated['user_ids'] ?? null
        );

        return redirect()->back()->with('flash', [
            'message' => __('Rule successfully created')
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
                'individual_number_value' => $this->numberValueFor($shiftRule->trigger_type, $validated),
                'warning_color' => $validated['warning_color'],
                'default_compensation_days' => $validated['default_compensation_days'] ?? null,
                'default_compensation_deadline_days' => $validated['default_compensation_deadline_days'] ?? null,
            ],
            $validated['contract_ids'] ?? null,
            $validated['user_ids'] ?? null
        );

        return redirect()->back()->with('flash', [
            'message' => __('Rule successfully updated')
        ]);
    }

    private function numberValueFor(string $triggerType, array $validated): float
    {
        if (in_array($triggerType, ShiftRuleService::ruleTypesWithoutValue(), true)) {
            return 0.0;
        }

        return (float) ($validated['individual_number_value'] ?? 0);
    }

    public function destroy(ShiftRule $shiftRule): RedirectResponse
    {
        $this->shiftRuleService->deleteRule($shiftRule);

        return redirect()->back()->with('flash', [
            'message' => __('Rule successfully deleted')
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
            'message' => __('Rule assignments successfully updated')
        ]);
    }

    /**
     * Regelprüfung für einen Zeitraum (optional eine Person) anstoßen. API-Endpunkt: liefert JSON,
     * keine Inertia-Seite (kein Frontend rendert das Ergebnis mehr).
     */
    public function validateRules(ValidateShiftRulesRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);

            if (!empty($validated['user_id'])) {
                $user = User::findOrFail($validated['user_id']);
                $violations = $this->shiftRuleService->validateRulesForUser($user, $startDate, $endDate);
            } else {
                $violations = $this->shiftRuleService->validateShiftRulesForDateRange($startDate, $endDate);
            }

            return new JsonResponse([
                'violations' => $this->shiftRuleService->mapViolationsToArray($violations),
                'violationsCount' => $violations->count(),
                'dateRange' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString()
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error validating rules: ' . $e->getMessage()
            ], 422);
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
                'message' => __('Status successfully updated')
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating status: ' . $e->getMessage());
        }
    }

    public function assignContracts(AssignContractsToRuleRequest $request, ShiftRule $shiftRule): RedirectResponse
    {
        $this->shiftRuleService->syncContractsForRule($shiftRule, $request->validated()['contract_ids']);

        return redirect()->back()->with('flash', [
            'message' => __('Contracts successfully assigned')
        ]);
    }

    public function assignUsers(AssignUsersToRuleRequest $request, ShiftRule $shiftRule): RedirectResponse
    {
        $this->shiftRuleService->syncUsersForRule($shiftRule, $request->validated()['user_ids']);

        return redirect()->back()->with('flash', [
            'message' => __('Users successfully assigned')
        ]);
    }

    public function resolveViolation(Request $request, ShiftRuleViolation $violation): RedirectResponse
    {
        $this->shiftRuleService->resolveViolation($violation, auth()->id());

        return redirect()->back()->with('flash', [
            'message' => __('Rule violation successfully resolved')
        ]);
    }

    public function ignoreViolation(Request $request, ShiftRuleViolation $violation): RedirectResponse
    {
        $validated = $request->validate([
            'ignore_reason' => 'required|string|max:500',
        ]);

        $this->shiftRuleService->ignoreViolation($violation, auth()->id(), $validated['ignore_reason']);

        return redirect()->back()->with('flash', [
            'message' => __('Rule violation successfully ignored')
        ]);
    }

    /**
     * DP-17 Verlauf: Activity-Log des Verstoßes und seiner Ersatzfreitage plus Genehmigungsvermerk
     * (bearbeitet/ignoriert von … am …) und ob eine Nachbearbeitung noch möglich ist.
     */
    public function violationHistory(ShiftRuleViolation $violation): JsonResponse
    {
        $violation->loadMissing(['resolvedByUser:id,first_name,last_name']);

        return new JsonResponse([
            'violation' => [
                'id' => $violation->id,
                'status' => $violation->status,
                'resolved_at' => $violation->resolved_at?->toIso8601String(),
                'resolved_by_user' => $violation->resolvedByUser ? [
                    'first_name' => $violation->resolvedByUser->first_name,
                    'last_name' => $violation->resolvedByUser->last_name,
                ] : null,
                'has_granted_compensation' => $this->shiftRuleService->hasGrantedCompensation($violation),
                'can_reprocess' => $violation->status === 'resolved'
                    && !$this->shiftRuleService->hasGrantedCompensation($violation),
            ],
            'entries' => $this->shiftRuleService->getViolationHistory($violation),
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
            'message' => __('Rule violation successfully created')
        ]);
    }

    /**
     * Verstoß bearbeiten (Ersatzfreitage buchen). DP-17: auch ein bereits bearbeiteter Verstoß darf
     * nachbearbeitet werden, solange keiner seiner Ersatzfreitage gewährt wurde — sonst 422.
     */
    public function processViolation(ProcessViolationRequest $request, ShiftRuleViolation $violation): RedirectResponse
    {
        if (!in_array($violation->status, ['active', 'resolved'], true)) {
            return redirect()->back()->with('error', __('Violation is not active.'));
        }

        if ($violation->status === 'resolved' && $this->shiftRuleService->hasGrantedCompensation($violation)) {
            abort(
                422,
                __('This rule violation cannot be edited anymore: a compensation day has already been granted.')
            );
        }

        $validated = $request->validated();

        $days = (float) $validated['compensation_days'];
        if (round($days * 2) !== (float) ($days * 2)) {
            return redirect()->back()->withErrors([
                'compensation_days' => 'Compensation days must be in 0.5 increments.',
            ]);
        }

        $attributes = [
            'compensation_days' => $validated['compensation_days'],
            'compensation_deadline' => $validated['compensation_deadline'],
            'compensation_reason' => $validated['compensation_reason'] ?? null,
            'for_holiday' => $validated['for_holiday'] ?? false,
            'half_day_period' => $validated['half_day_period'] ?? null,
        ];

        if ($violation->status === 'resolved') {
            $this->shiftRuleService->reprocessViolation($violation, $attributes, auth()->id());
        } else {
            $this->shiftRuleService->processViolation($violation, $attributes, auth()->id());
        }

        app(\Artwork\Modules\User\Services\WorkingHourCacheService::class)
            ->forgetForEntity('user', $violation->user_id);

        return redirect()->back()->with('flash', [
            'message' => __('Rule violation successfully processed')
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
            'half_day_period' => 'nullable|in:morning,afternoon,both',
        ]);

        $grantedDate = $validated['granted_date'];

        // Check if user has shifts on that date (unified shift_workers pivot)
        $shiftsOnDate = $this->userShiftWorkersOnDateQuery($compensationDayOff->user_id, $grantedDate)->count();

        if (!empty($validated['check_only'])) {
            return new JsonResponse([
                'has_shifts' => $shiftsOnDate > 0,
                'shift_count' => $shiftsOnDate,
            ]);
        }

        // Remove shifts if requested - via ShiftWorkerService so activity log, change
        // records and notifications fire like a manual removal from the shift plan.
        if (!empty($validated['remove_shifts']) && $shiftsOnDate > 0) {
            $shiftWorkerService = app(\Artwork\Modules\Shift\Services\ShiftWorkerService::class);
            $this->userShiftWorkersOnDateQuery($compensationDayOff->user_id, $grantedDate)
                ->with('shift')
                ->get()
                ->each(fn (\Artwork\Modules\Shift\Models\ShiftWorker $pivot) => $shiftWorkerService->removeFromShift(
                    $pivot,
                    true,
                    app(\Artwork\Modules\Notification\Services\NotificationService::class),
                    app(\Artwork\Modules\Vacation\Services\VacationConflictService::class),
                    app(\Artwork\Modules\Availability\Services\AvailabilityConflictService::class),
                    app(\Artwork\Modules\Change\Services\ChangeService::class)
                ));
        }

        $period = $validated['half_day_period'] ?? null;
        $isHalfDay = (float) $compensationDayOff->value < 1.0;

        // "both" pairs a second open half day on the same date (morning + afternoon = whole day off).
        if ($isHalfDay && $period === 'both') {
            $secondHalf = $this->compensationDayOffRepository()
                ->findOpenHalfForUserExcept($compensationDayOff->user_id, $compensationDayOff->id);

            if (!$secondHalf) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'half_day_period' => 'Für "Beides" werden zwei offene halbe freie Tage benötigt.',
                ]);
            }

            $compensationDayOff->update([
                'half_day_period' => 'morning',
                'granted_date' => $grantedDate,
                'granted_by' => auth()->id(),
                'granted_at' => now(),
            ]);
            $secondHalf->update([
                'half_day_period' => 'afternoon',
                'granted_date' => $grantedDate,
                'granted_by' => auth()->id(),
                'granted_at' => now(),
            ]);
        } else {
            $compensationDayOff->update([
                'half_day_period' => $isHalfDay ? $period : null,
                'granted_date' => $grantedDate,
                'granted_by' => auth()->id(),
                'granted_at' => now(),
            ]);
        }

        // Invalidate WorkingHourCache
        app(\Artwork\Modules\User\Services\WorkingHourCacheService::class)
            ->forgetForEntity('user', $compensationDayOff->user_id);

        // Immediately re-validate shift rules for the affected day so HFT/shift conflicts surface at once.
        if ($compensationDayOff->user) {
            $day = Carbon::parse($grantedDate);
            $this->shiftRuleService->validateRulesForUser($compensationDayOff->user, $day->copy(), $day->copy());
        }

        return redirect()->back()->with('flash', [
            'message' => __('Compensation day successfully granted')
        ]);
    }

    /**
     * Schicht-Zuweisungen eines Users an einem Tag aus der vereinheitlichten shift_workers-Pivot.
     * Matcht Pivot-Zeitraum (worker-individuelle Zeiten) ODER Schicht-Zeitraum.
     */
    private function userShiftWorkersOnDateQuery(int $userId, string $date): \Illuminate\Database\Eloquent\Builder
    {
        return \Artwork\Modules\Shift\Models\ShiftWorker::query()
            ->where('employable_type', User::class)
            ->where('employable_id', $userId)
            ->where(function ($query) use ($date): void {
                $query->where(function ($subQuery) use ($date): void {
                    $subQuery->whereDate('start_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date);
                })->orWhereHas('shift', function ($subQuery) use ($date): void {
                    $subQuery->whereDate('start_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date);
                });
            });
    }

    private function compensationDayOffRepository(): CompensationDayOffRepository
    {
        return app(CompensationDayOffRepository::class);
    }

    public function checkCompensationDay(Request $request, CompensationDayOff $compensationDayOff): JsonResponse
    {
        $validated = $request->validate([
            'granted_date' => 'required|date',
        ]);

        $shiftsOnDate = $this->userShiftWorkersOnDateQuery(
            $compensationDayOff->user_id,
            $validated['granted_date']
        )->count();

        // Special day check: granting a half day off on a "Sondertag" violates the halfDayOffOnSpecialDay rule.
        $specialDayRule = null;
        $isHalfDay = (float) $compensationDayOff->value < 1.0;

        if (
            $isHalfDay
            && \Artwork\Modules\Holidays\Models\Holiday::isSpecialDay($validated['granted_date'])
            && $compensationDayOff->user
        ) {
            $rule = $this->shiftRuleService->getActiveRuleByTriggerTypeForUser(
                $compensationDayOff->user,
                'halfDayOffOnSpecialDay'
            );
            if ($rule) {
                $specialDayRule = [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'description' => $rule->description,
                ];
            }
        }

        return new JsonResponse([
            'has_shifts' => $shiftsOnDate > 0,
            'shift_count' => $shiftsOnDate,
            'special_day' => $specialDayRule !== null,
            'special_day_rule' => $specialDayRule,
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
            'message' => __('Compensation day revoked successfully')
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
            'message' => __('Compensation day successfully deleted')
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

        // Load shifts for the week from the unified shift_workers pivot. The pivot carries
        // per-worker dates/times that may differ from the shift itself (individual shift times).
        $weekStart = $monday->toDateString();
        $weekEnd = $sunday->toDateString();
        $shifts = $user->shifts()
            ->where(function ($query) use ($weekStart, $weekEnd): void {
                $query->where(function ($subQuery) use ($weekStart, $weekEnd): void {
                    $subQuery->whereDate('shifts.start_date', '<=', $weekEnd)
                        ->whereDate('shifts.end_date', '>=', $weekStart);
                })->orWhere(function ($subQuery) use ($weekStart, $weekEnd): void {
                    $subQuery->whereDate('shift_workers.start_date', '<=', $weekEnd)
                        ->whereDate('shift_workers.end_date', '>=', $weekStart);
                });
            })
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

            $dayShifts = $shifts->filter(function ($shift) use ($dateStr) {
                $shiftStart = $shift->pivot->start_date ?? $shift->start_date;
                $shiftEnd = $shift->pivot->end_date ?? $shift->end_date;
                if (!$shiftStart || !$shiftEnd) {
                    return false;
                }
                return Carbon::parse($shiftStart)->toDateString() <= $dateStr
                    && Carbon::parse($shiftEnd)->toDateString() >= $dateStr;
            })->map(fn ($shift) => [
                'start' => substr((string) ($shift->pivot->start_time ?? $shift->start ?? ''), 0, 5),
                'end' => substr((string) ($shift->pivot->end_time ?? $shift->end ?? ''), 0, 5),
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
            'half_day_period' => 'nullable|in:morning,afternoon',
        ]);

        $isHalfDay = (float) $validated['value'] < 1.0;

        CompensationDayOff::create([
            'user_id' => $validated['user_id'],
            'violation_id' => null,
            'value' => $validated['value'],
            'deadline' => $validated['deadline'],
            'reason' => $validated['reason'] ?? null,
            'for_holiday' => $validated['for_holiday'] ?? false,
            // The period only applies to a half day.
            'half_day_period' => $isHalfDay ? ($validated['half_day_period'] ?? null) : null,
        ]);

        return redirect()->back()->with('flash', [
            'message' => __('Compensation day created successfully')
        ]);
    }
}

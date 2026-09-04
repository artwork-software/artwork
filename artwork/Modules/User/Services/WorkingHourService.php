<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Holidays\Services\SpecialDayService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Wochen-/Zeitraumwerte für Schichtplan-Kacheln (KW-Spalte, AZK-Badge). Tageswerte kommen
 * ausschließlich aus dem WorkTimeCalculationService (keine eigene Soll/Ist-Logik mehr).
 */
class WorkingHourService
{
    public function __construct(
        private UserRepository $userRepository,
        private WorkingHourCacheService $workingHourCacheService,
        private WorkTimeCalculationService $workTimeCalculationService,
        private SpecialDayService $specialDayService,
        private ContractSettingsResolver $contractSettings,
    ) {
    }

    /**
     * Ist-Minuten im Zeitraum (Buchung schlägt Schichten, keine Doppelzählung).
     */
    public function calculateShiftTime(
        User|Freelancer|ServiceProvider $entity,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $total = 0;
        foreach ($this->workTimeCalculationService->breakdownForRange($entity, $startDate, $endDate) as $day) {
            $total += (int) $day['actual'];
        }

        return max(0, $total);
    }

    /**
     * Klassisches Format "2h 30m" / "-1h 30m" (KW-Spalte, Freelancer-/Dienstleister-Ansichten).
     */
    public function convertMinutesInHours(int $minutes, bool $forcePositive = false): string
    {
        $absMinutes = abs($minutes);
        $hours = intdiv($absMinutes, 60);
        $remainingMinutes = $absMinutes % 60;
        $sign = (!$forcePositive && $minutes < 0) ? '-' : '';
        return sprintf('%s%dh %dm', $sign, $hours, $remainingMinutes);
    }

    /**
     * AZK-Badge-Format mit Vorzeichen: "+10:30 h" / "−2:00 h".
     */
    public function formatSignedHours(int $minutes): string
    {
        return WorkTimeCalculationService::formatSignedHours($minutes);
    }

    /**
     * Unsigniertes Dienstplan-Format "38:00 h".
     */
    public function formatHours(int $minutes): string
    {
        return WorkTimeCalculationService::formatHours($minutes);
    }

    /**
     * Zusatzdaten für das Stundenkonto-Badge einer Person (nur wenn Stunden sichtbar sind).
     *
     * @return array{workTimeBalance: string|null, workTimeBalanceFormatted: string|null, workTimeBalanceMinutes: int|null}
     */
    public function workTimeBalanceData(User $user, bool $showHours): array
    {
        $minutes = (int) ($user->work_time_balance ?? 0);

        return [
            'workTimeBalance' => $showHours ? $this->convertMinutesInHours($minutes) : null,
            'workTimeBalanceFormatted' => $showHours ? $this->formatSignedHours($minutes) : null,
            'workTimeBalanceMinutes' => $showHours ? $minutes : null,
        ];
    }

    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $desiredResourceClass
     * @param bool $addVacationsAndAvailabilities
     * @param User|null $currentUser
     * @param array<int>|null $craftIds
     * @return array<int, array<string, mixed>>
     */
    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded,Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getUsersWithPlannedWorkingHours(
        Carbon $startDate,
        Carbon $endDate,
        string $desiredResourceClass,
        bool $addVacationsAndAvailabilities = false,
        ?User $currentUser = null,
        ?array $craftIds = null
    ): array {
        // Im Konstruktor kann das zu circluar dependency führen, deswegen über den Container
        $workerShiftPlanService = app(\Artwork\Modules\Worker\Services\WorkerShiftPlanService::class);
        $workerService = app(\Artwork\Modules\Worker\Services\WorkerService::class);

        $workers = $craftIds !== null
            ? $this->userRepository->getWorkersByIds(
                app(\Artwork\Modules\Craft\Repositories\CraftRepository::class)->getWorkerIdsByCraftIds($craftIds)['user_ids'],
                $startDate,
                $endDate
            )
            : $this->userRepository->getWorkers($startDate, $endDate);
        $workers = $workerShiftPlanService->loadWorkerRelations($workers, $startDate, $endDate);
        $workers = $workerShiftPlanService->filterByQualifications($workers, $currentUser);
        $qualificationsCache = $workerService->buildQualificationsCache($workers);

        // Stundenkonto/KW-Stunden anderer Personen nur mit Berechtigung; eigene Werte immer.
        // Ohne Berechtigung wird für die anderen erst gar nichts berechnet.
        $canSeeWorkerHours = $currentUser?->can(PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value) ?? false;
        $weeklyWorkingHoursCache = $this->precomputeWeeklyWorkingHours(
            $canSeeWorkerHours
                ? $workers
                : $workers->filter(static fn (User $worker) => $worker->id === $currentUser?->id),
            $startDate,
            $endDate
        );

        $workerIds = $workers->pluck('id')->all();

        // Batch-load all availabilities in one query instead of N+1
        $availabilitiesByUser = collect();
        if ($addVacationsAndAvailabilities) {
            $availabilitiesByUser = Availability::query()
                ->where('available_type', User::class)
                ->whereIn('available_id', $workerIds)
                ->betweenDates($startDate, $endDate)
                ->get()
                ->groupBy('available_id')
                ->map(fn ($availabilities) => $availabilities->groupBy('formatted_date'));
        }

        // Batch-load shift rule violations for all users in date range
        $violationsByUser = \Artwork\Modules\Shift\Models\ShiftRuleViolation::query()
            ->with(['shiftRule:id,name,description,trigger_type,warning_color,default_compensation_days,default_compensation_deadline_days'])
            ->whereIn('user_id', $workerIds)
            ->whereBetween('violation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereIn('status', ['active', 'resolved'])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($violations) => $violations->groupBy(fn ($v) => $v->violation_date->format('Y-m-d')));

        // Batch-load granted compensation day offs for all users in date range
        $compensationDaysByUser = CompensationDayOff::whereIn('user_id', $workerIds)
            ->whereNotNull('granted_date')
            ->whereBetween('granted_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->with([
                'violation:id,shift_rule_id',
                'violation.shiftRule:id,name',
                'grantedByUser:id,first_name,last_name',
            ])
            ->get()
            ->groupBy('user_id')
            ->map(fn ($days) => $days->groupBy(fn ($d) => $d->granted_date->format('Y-m-d')));

        $usersWithPlannedWorkingHours = [];

        /** @var User $user */
        foreach ($workers as $user) {
            /** @var JsonResource $desiredResourceClass */
            $desiredUserResource = $desiredResourceClass::make($user);

            $showHours = $canSeeWorkerHours || $user->id === $currentUser?->id;
            $additionalData = array_merge(
                $this->workTimeBalanceData($user, $showHours),
                ['weeklyWorkingHours' => $showHours ? ($weeklyWorkingHoursCache[$user->id] ?? []) : []]
            );

            $userData = $workerShiftPlanService->buildWorkerData(
                $user,
                $desiredUserResource,
                $qualificationsCache,
                $startDate,
                $endDate,
                $addVacationsAndAvailabilities,
                $additionalData
            );

            if ($addVacationsAndAvailabilities) {
                $userData['availabilities'] = $availabilitiesByUser->get($user->id, collect());
            }

            $userData['violations'] = $violationsByUser->get($user->id, collect());
            $userData['compensation_day_offs'] = $compensationDaysByUser->get($user->id, collect());
            // contract.userContract ist in loadWorkerRelations eager geladen — kein activeWorkContract()
            // (das würde pro User zwei Queries feuern). Zuweisung vor Vorlage, 0 auf der Zuweisung =
            // nicht gesetzt (siehe ContractSettingsResolver::compensationPeriod)
            $userData['compensation_period'] = $this->contractSettings->compensationPeriod($user);

            $usersWithPlannedWorkingHours[] = $userData;
        }
        if ($currentUser && $currentUser->getAttribute('shift_plan_user_sort_by_id')) {
            usort($usersWithPlannedWorkingHours, static function ($a, $b) use ($currentUser) {
                return match ($currentUser->getAttribute('shift_plan_user_sort_by_id')) {
                    'ALPHABETICALLY_ASCENDING_FIRST_NAME' =>
                    strcmp($a['user']['first_name'], $b['user']['first_name']),
                    'ALPHABETICALLY_DESCENDING_FIRST_NAME' =>
                    strcmp($b['user']['first_name'], $a['user']['first_name']),
                    'ALPHABETICALLY_ASCENDING_LAST_NAME' =>
                    strcmp($a['user']['last_name'], $b['user']['last_name']),
                    'ALPHABETICALLY_DESCENDING_LAST_NAME' =>
                    strcmp($b['user']['last_name'], $a['user']['last_name']),
                    default => 0,
                };
            });
        }
        return $usersWithPlannedWorkingHours;
    }

    /**
     * Wochenperioden im Zeitraum (ISO-Woche, auf den Zeitraum beschnitten).
     *
     * @return array<int, array{weekStart: Carbon, actualStart: Carbon, actualEnd: Carbon, weekNumber: string, year: int, isoWeek: int}>
     */
    private function buildWeekPeriods(Carbon $startDate, Carbon $endDate): array
    {
        $period = CarbonPeriod::create($startDate->copy()->startOfWeek(), '1 week', $endDate->copy()->endOfWeek());
        $weekPeriods = [];

        foreach ($period as $weekStart) {
            $weekEnd = $weekStart->copy()->endOfWeek();
            $weekPeriods[] = [
                'weekStart' => $weekStart,
                'actualStart' => $weekStart->greaterThanOrEqualTo($startDate) ? $weekStart->copy() : $startDate->copy(),
                'actualEnd' => $weekEnd->lessThanOrEqualTo($endDate) ? $weekEnd->copy() : $endDate->copy(),
                'weekNumber' => ltrim($weekStart->format('W'), '0'),
                'year' => (int) $weekStart->format('o'),
                'isoWeek' => (int) $weekStart->format('W'),
            ];
        }

        return $weekPeriods;
    }

    /**
     * Wochenwerte aus den Tages-Breakdowns; behält die Keys daily_target/planned/difference/isMinus
     * ("2h 0m", Freelancer-/Dienstleister-Ansichten, Tests) und liefert zusätzlich die Rohminuten
     * sowie die *_formatted-Keys im einheitlichen Dienstplan-Format "H:MM h" (signiert bei der
     * Differenz), die ShiftPlan.vue in der KW-Spalte rendert.
     *
     * @param array<string, array<string, mixed>> $breakdown 'Y-m-d' => Tageswerte
     * @return array<string, mixed>
     */
    private function buildWeekData(array $breakdown, Carbon $actualStart, Carbon $actualEnd): array
    {
        $totalPlannedMinutes = 0;
        $totalExpectedMinutes = 0;
        $specialDays = 0;
        $reducedDays = 0;

        $current = $actualStart->copy()->startOfDay();
        $last = $actualEnd->copy()->startOfDay();
        while ($current->lte($last)) {
            $day = $breakdown[$current->toDateString()] ?? null;
            if ($day !== null) {
                $totalPlannedMinutes += (int) $day['actual'];
                $totalExpectedMinutes += (int) $day['target'];
                if ($day['is_special_day']) {
                    $specialDays++;
                }
                if ($day['reduction_reason'] !== null) {
                    $reducedDays++;
                }
            }
            $current->addDay();
        }

        $differenceInMinutes = $totalPlannedMinutes - $totalExpectedMinutes;

        return [
            'daily_target' => $this->convertMinutesInHours($totalExpectedMinutes, true),
            'planned' => $this->convertMinutesInHours($totalPlannedMinutes, true),
            'difference' => $this->convertMinutesInHours($differenceInMinutes),
            'isMinus' => $differenceInMinutes < 0,
            'target_minutes' => $totalExpectedMinutes,
            'planned_minutes' => $totalPlannedMinutes,
            'difference_minutes' => $differenceInMinutes,
            'difference_signed' => $this->formatSignedHours($differenceInMinutes),
            'planned_formatted' => $this->formatHours($totalPlannedMinutes),
            'daily_target_formatted' => $this->formatHours($totalExpectedMinutes),
            'difference_formatted' => $this->formatSignedHours($differenceInMinutes),
            'special_days' => $specialDays,
            'reduced_days' => $reducedDays,
        ];
    }

    /**
     * Precompute weekly working hours for all users at once (Kachel-KW-Spalte).
     *
     * @param Collection $users Collection of users
     * @param Carbon $startDate Start date
     * @param Carbon $endDate End date
     * @return array Array of weekly working hours indexed by user ID
     */
    private function precomputeWeeklyWorkingHours(Collection $users, Carbon $startDate, Carbon $endDate): array
    {
        $weeklyWorkingHoursCache = [];
        $weekPeriods = $this->buildWeekPeriods($startDate, $endDate);

        // Feiertags-Ausgleichstage und Sondertage einmal für alle User laden statt pro User im Loop
        $holidayCompDaysByUser = CompensationDayOff::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereNotNull('granted_date')
            ->where('for_holiday', true)
            ->whereBetween('granted_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('user_id');
        $specialDays = $this->specialDayService->specialDaysBetween($startDate, $endDate);

        foreach ($users as $user) {
            $userId = $user->id;
            $entityType = WorkingHourCacheService::entityType($user);
            $weeklyWorkingHoursCache[$userId] = [];

            // Check cache for each week first
            $uncachedWeeks = [];
            foreach ($weekPeriods as $index => $weekPeriod) {
                $cached = $this->workingHourCacheService->getWeeklyData(
                    $entityType,
                    $userId,
                    $weekPeriod['year'],
                    $weekPeriod['isoWeek']
                );

                if ($cached !== null) {
                    $weeklyWorkingHoursCache[$userId][$weekPeriod['weekNumber']] = $cached;
                } else {
                    $uncachedWeeks[] = $index;
                }
            }

            // Skip expensive computation if all weeks are cached
            if (empty($uncachedWeeks)) {
                continue;
            }

            $breakdown = $this->workTimeCalculationService->breakdownForRange($user, $startDate, $endDate, [
                'special_days' => $specialDays,
                'holiday_comp_days' => $holidayCompDaysByUser->get($userId, collect()),
            ]);

            foreach ($uncachedWeeks as $index) {
                $weekPeriod = $weekPeriods[$index];
                $weekData = $this->buildWeekData($breakdown, $weekPeriod['actualStart'], $weekPeriod['actualEnd']);
                $weeklyWorkingHoursCache[$userId][$weekPeriod['weekNumber']] = $weekData;

                $this->workingHourCacheService->setWeeklyData(
                    $entityType,
                    $userId,
                    $weekPeriod['year'],
                    $weekPeriod['isoWeek'],
                    $weekData
                );
            }
        }

        return $weeklyWorkingHoursCache;
    }

    /**
     * Wochenwerte für EINE Person (Einzel-Reload, Freelancer-/Dienstleister-Ansichten).
     *
     * @return array<string, array<string, mixed>>
     */
    public function calculateWeeklyWorkingHours(
        User|Freelancer|ServiceProvider $entity,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        $entityType = WorkingHourCacheService::entityType($entity);
        $entityId = $entity->id;

        $weekPeriods = $this->buildWeekPeriods($startDate, $endDate);
        $uncachedIndexes = [];
        $weeklyWorkingHours = [];

        foreach ($weekPeriods as $index => $wp) {
            $cached = $this->workingHourCacheService->getWeeklyData($entityType, $entityId, $wp['year'], $wp['isoWeek']);

            if ($cached !== null) {
                $weeklyWorkingHours[$wp['weekNumber']] = $cached;
            } else {
                $uncachedIndexes[] = $index;
            }
        }

        if (empty($uncachedIndexes)) {
            return $weeklyWorkingHours;
        }

        $breakdown = $this->workTimeCalculationService->breakdownForRange($entity, $startDate, $endDate);

        foreach ($uncachedIndexes as $index) {
            $wp = $weekPeriods[$index];
            $weekData = $this->buildWeekData($breakdown, $wp['actualStart'], $wp['actualEnd']);
            $weeklyWorkingHours[$wp['weekNumber']] = $weekData;

            $this->workingHourCacheService->setWeeklyData(
                $entityType,
                $entityId,
                $wp['year'],
                $wp['isoWeek'],
                $weekData
            );
        }

        return $weeklyWorkingHours;
    }
}

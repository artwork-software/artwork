<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\Shift\RuleChecks\AbstractRuleCheck;
use Spatie\Activitylog\Models\Activity;
use Artwork\Modules\Shift\Repositories\ShiftRuleRepository;
use Artwork\Modules\Shift\Repositories\ShiftRuleViolationRepository;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ShiftRuleService
{
    public function __construct(
        private readonly ShiftRuleRepository $shiftRuleRepository,
        private readonly ShiftRuleViolationRepository $shiftRuleViolationRepository,
        private readonly CompensationDayOffRepository $compensationDayOffRepository,
        private readonly ShiftRuleCheckFactory $ruleCheckFactory,
    ) {
    }

    public function getAllWithRelations(): EloquentCollection
    {
        return $this->shiftRuleRepository->getAllWithRelations();
    }

    public function getActiveRules(array $columns = ['*']): EloquentCollection
    {
        return $this->shiftRuleRepository->getActive($columns);
    }

    public function createRule(array $attributes, ?array $contractIds = null, ?array $userIds = null): ShiftRule
    {
        $rule = $this->shiftRuleRepository->createRule($attributes);

        if (!empty($contractIds)) {
            $rule->contracts()->sync($contractIds);
        }

        if (!empty($userIds)) {
            $rule->usersToNotify()->sync($userIds);
        }

        return $rule;
    }

    public function updateRule(
        ShiftRule $rule,
        array $attributes,
        ?array $contractIds = null,
        ?array $userIds = null
    ): ShiftRule {
        $this->shiftRuleRepository->update($rule, $attributes);

        $rule->contracts()->sync($contractIds ?? []);
        $rule->usersToNotify()->sync($userIds ?? []);

        return $rule;
    }

    public function deleteRule(ShiftRule $rule): bool
    {
        return $this->shiftRuleRepository->delete($rule);
    }

    public function syncContractsForRule(ShiftRule $rule, array $contractIds): void
    {
        $rule->contracts()->sync($contractIds);
    }

    public function syncUsersForRule(ShiftRule $rule, array $userIds): void
    {
        $rule->usersToNotify()->sync($userIds);
    }

    public function updateContractAssignments(UserContract $contract, array $ruleIds): void
    {
        $contract->shiftRules()->sync($ruleIds);
    }

    public function getActiveViolations(): EloquentCollection
    {
        return $this->shiftRuleViolationRepository->getActiveWithRelations();
    }

    public function getViolationsForDateRange(
        string $startDate,
        string $endDate,
        ?array $userIds = null
    ): Collection {
        return $this->shiftRuleViolationRepository
            ->getActiveForDateRange($startDate, $endDate, $userIds)
            ->groupBy('user_id')
            ->map(fn ($userViolations) => $userViolations->groupBy(
                fn ($v) => $v->violation_date->format('Y-m-d')
            ));
    }

    public function createManualViolation(array $attributes): ShiftRuleViolation
    {
        return $this->shiftRuleViolationRepository->createViolation($attributes);
    }

    public function resolveViolation(ShiftRuleViolation $violation, ?int $userId = null): void
    {
        $this->shiftRuleViolationRepository->resolve($violation, $userId);
    }

    public function ignoreViolation(ShiftRuleViolation $violation, ?int $userId = null, ?string $ignoreReason = null): void
    {
        $this->shiftRuleViolationRepository->ignore($violation, $userId, $ignoreReason);
    }

    public function updateViolationStatus(int $violationId, string $status, ?int $userId = null): void
    {
        $violation = $this->shiftRuleViolationRepository->findOrFail($violationId);

        if ($status === 'resolved') {
            $this->shiftRuleViolationRepository->resolve($violation, $userId);
        } else {
            $this->shiftRuleViolationRepository->ignore($violation, $userId);
        }
    }

    public function processViolation(ShiftRuleViolation $violation, array $attributes, int $userId): void
    {
        $this->shiftRuleViolationRepository->update($violation, $attributes);

        $violation->resolve($userId);

        $this->compensationDayOffRepository->createFromProcessing(
            $violation->user_id,
            $violation->id,
            (float) $attributes['compensation_days'],
            $attributes['compensation_deadline'],
            $attributes['compensation_reason'] ?? null,
            $attributes['for_holiday'] ?? false,
            $attributes['half_day_period'] ?? null
        );
    }

    /**
     * DP-17 Nachbearbeitung: Ein bereits bearbeiteter (resolved) Verstoß darf erneut bearbeitet werden,
     * solange keiner seiner Ersatzfreitage gewährt wurde. Ungewährte Ersatzfreitage werden ersetzt,
     * die Felder aktualisiert und der alte/neue Stand im Activity-Log festgehalten.
     */
    public function hasGrantedCompensation(ShiftRuleViolation $violation): bool
    {
        return $violation->compensationDayOffs()->whereNotNull('granted_at')->exists();
    }

    public function reprocessViolation(ShiftRuleViolation $violation, array $attributes, int $userId): void
    {
        $before = [
            'compensation_days' => $violation->compensation_days,
            'compensation_deadline' => $violation->compensation_deadline?->toDateString(),
            'compensation_reason' => $violation->compensation_reason,
            'for_holiday' => (bool) $violation->compensationDayOffs()->where('for_holiday', true)->exists(),
            'half_day_period' => $violation->compensationDayOffs()->whereNotNull('half_day_period')
                ->value('half_day_period'),
        ];

        $violation->compensationDayOffs()->whereNull('granted_at')->get()->each->delete();

        $this->shiftRuleViolationRepository->update($violation, [
            'compensation_days' => $attributes['compensation_days'],
            'compensation_deadline' => $attributes['compensation_deadline'],
            'compensation_reason' => $attributes['compensation_reason'] ?? null,
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ]);

        $this->compensationDayOffRepository->createFromProcessing(
            $violation->user_id,
            $violation->id,
            (float) $attributes['compensation_days'],
            $attributes['compensation_deadline'],
            $attributes['compensation_reason'] ?? null,
            $attributes['for_holiday'] ?? false,
            $attributes['half_day_period'] ?? null
        );

        $after = [
            'compensation_days' => $attributes['compensation_days'],
            'compensation_deadline' => $attributes['compensation_deadline'],
            'compensation_reason' => $attributes['compensation_reason'] ?? null,
            'for_holiday' => (bool) ($attributes['for_holiday'] ?? false),
            'half_day_period' => $attributes['half_day_period'] ?? null,
        ];

        activity('shift_rule_violation')
            ->performedOn($violation)
            ->causedBy($userId ? User::find($userId) : null)
            ->withProperties(['old' => $before, 'attributes' => $after])
            ->event('reprocessed')
            ->log('Rule violation reprocessed');
    }

    /**
     * Verlauf eines Verstoßes: Activity-Log-Einträge des Verstoßes selbst und seiner Ersatzfreitage
     * (Spatie activity_log via subject_type/subject_id), chronologisch mit Verursacher, Ereignis und
     * geänderten Feldern (alt -> neu).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getViolationHistory(ShiftRuleViolation $violation): array
    {
        // Auch bereits gelöschte Ersatzfreitage (Nachbearbeitung) sollen im Verlauf sichtbar bleiben:
        // ihre IDs stehen nur noch im "created"-Eintrag des Activity-Logs (properties.attributes.violation_id).
        $dayOffIds = $violation->compensationDayOffs()->pluck('id')
            ->merge(
                Activity::query()
                    ->where('subject_type', CompensationDayOff::class)
                    ->where('properties->attributes->violation_id', $violation->id)
                    ->pluck('subject_id')
            )
            ->filter()
            ->unique()
            ->values();

        $activities = Activity::query()
            ->with('causer')
            ->where(function ($query) use ($violation, $dayOffIds): void {
                $query->where(function ($sub) use ($violation): void {
                    $sub->where('subject_type', ShiftRuleViolation::class)
                        ->where('subject_id', $violation->id);
                });
                if ($dayOffIds->isNotEmpty()) {
                    $query->orWhere(function ($sub) use ($dayOffIds): void {
                        $sub->where('subject_type', CompensationDayOff::class)
                            ->whereIn('subject_id', $dayOffIds);
                    });
                }
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return $activities->map(function (Activity $activity): array {
            $properties = $activity->properties ?? collect();
            $new = (array) ($properties['attributes'] ?? []);
            $old = (array) ($properties['old'] ?? []);

            $changes = [];
            foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
                $oldValue = $old[$field] ?? null;
                $newValue = $new[$field] ?? null;
                if ($oldValue === $newValue) {
                    continue;
                }
                $changes[] = [
                    'field' => $field,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }

            $extra = collect($properties)->except(['attributes', 'old'])->all();

            return [
                'id' => $activity->id,
                'subject_type' => $activity->subject_type === CompensationDayOff::class
                    ? 'compensation_day_off'
                    : 'violation',
                'subject_id' => $activity->subject_id,
                'event' => $activity->event ?? $activity->description,
                'description' => $activity->description,
                'causer' => $activity->causer ? [
                    'id' => $activity->causer->id,
                    'first_name' => $activity->causer->first_name ?? null,
                    'last_name' => $activity->causer->last_name ?? null,
                ] : null,
                'changes' => $changes,
                'extra' => $extra,
                'created_at' => $activity->created_at?->toIso8601String(),
            ];
        })->values()->all();
    }

    /**
     * Regelprüfung für eine Person im Zeitraum. Nach dem Lauf werden automatische Verstöße, die der Lauf
     * NICHT (neu) erzeugt oder bestätigt hat, gelöscht (siehe removeStaleViolations). Ohne Vertrag bzw.
     * ohne zugeordnete Regeln gelten alle automatischen aktiven Verstöße im Zeitraum als veraltet.
     */
    public function validateRulesForUser(
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();

        $activeContract = $user->activeWorkContract();
        $rules = $activeContract ? $this->getRulesForContract($activeContract) : collect();

        // Je Regel das Fenster, das der Check tatsächlich beurteilt hat (Standard: der Zeitraum).
        $coveredRangeByRule = [];

        foreach ($rules as $rule) {
            if (!$this->ruleCheckFactory->has($rule->trigger_type)) {
                // Unbekannter Regeltyp (z. B. Altbestand): weder prüfen noch aufräumen.
                $coveredRangeByRule[$rule->id] = null;
                continue;
            }

            $ruleViolations = $this->checkRuleForUser($rule, $user, $startDate, $endDate);
            $violations = $violations->concat($ruleViolations);

            $check = $this->ruleCheckFactory->create($rule->trigger_type);
            $coveredRangeByRule[$rule->id] = $check instanceof AbstractRuleCheck
                ? $check->getCoveredRange($rule, $startDate, $endDate)
                : [$startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()];
        }

        $this->removeStaleViolations(
            $user,
            $startDate,
            $endDate,
            $violations->pluck('id')->filter()->unique()->all(),
            $coveredRangeByRule
        );

        return $violations;
    }

    /**
     * Automatische Auflösung: löscht Verstöße der Person, die
     *  (a) status = active,
     *  (b) is_manual = false,
     *  (c) kein Folge-Verstoß sind (parent_violation_id null — "Frist abgelaufen" bleibt bestehen),
     *  (d) noch keine Ersatzfreitage haben (sonst würden Buchungen verwaisen),
     *  (e) im vom jeweiligen Check abgedeckten Fenster liegen und
     *  (f) in diesem Lauf nicht erzeugt/bestätigt wurden ($keptViolationIds).
     * Verstöße zu Regeln, die der Person nicht mehr zugeordnet sind (oder ohne Vertrag), werden im
     * gesamten Zeitraum gelöscht. Bearbeitete (resolved) und ignorierte Verstöße bleiben unberührt.
     *
     * @param array<int, int> $keptViolationIds
     * @param array<int, array{0: Carbon, 1: Carbon}|null> $coveredRangeByRule
     */
    private function removeStaleViolations(
        User $user,
        Carbon $startDate,
        Carbon $endDate,
        array $keptViolationIds,
        array $coveredRangeByRule
    ): void {
        $baseQuery = fn () => ShiftRuleViolation::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_manual', false)
            ->whereNull('parent_violation_id')
            ->whereDoesntHave('compensationDayOffs')
            ->when($keptViolationIds !== [], fn ($q) => $q->whereNotIn('id', $keptViolationIds));

        // Regeln, die geprüft wurden: nur im abgedeckten Fenster aufräumen.
        foreach ($coveredRangeByRule as $ruleId => $range) {
            if ($range === null) {
                continue;
            }
            [$from, $to] = $range;
            $baseQuery()
                ->where('shift_rule_id', $ruleId)
                ->whereBetween('violation_date', [$from->toDateString(), $to->toDateString()])
                ->get()
                ->each->delete();
        }

        // Regeln, die der Person nicht (mehr) zugeordnet sind: im gesamten Zeitraum aufräumen.
        $checkedRuleIds = array_keys($coveredRangeByRule);
        $baseQuery()
            ->when($checkedRuleIds !== [], fn ($q) => $q->whereNotIn('shift_rule_id', $checkedRuleIds))
            ->whereBetween('violation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->each->delete();
    }

    public function checkRuleForUser(
        ShiftRule $rule,
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $ruleCheck = $this->ruleCheckFactory->create($rule->trigger_type);
        return $ruleCheck->check($rule, $user, $startDate, $endDate);
    }

    private function getRulesForContract(UserContract $contract): Collection
    {
        return ShiftRule::whereHas('contracts', function ($query) use ($contract): void {
            $query->where('contract_id', $contract->id);
        })->where('is_active', true)->get();
    }

    public function getActiveRuleByTriggerTypeForUser(User $user, string $triggerType): ?ShiftRule
    {
        $activeContract = $user->activeWorkContract();
        if (!$activeContract) {
            return null;
        }

        return $this->getRulesForContract($activeContract)
            ->firstWhere('trigger_type', $triggerType);
    }

    public function validateShiftRulesForDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        $violations = collect();

        // Personen mit Vertrag werden geprüft; Personen ohne Vertrag, die noch aktive automatische
        // Verstöße im Zeitraum haben, laufen ebenfalls durch, damit diese aufgeräumt werden.
        $usersWithStaleCandidates = ShiftRuleViolation::query()
            ->where('status', 'active')
            ->where('is_manual', false)
            ->whereNull('parent_violation_id')
            ->whereBetween('violation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('user_id');

        $users = User::query()
            ->where(function ($query) use ($usersWithStaleCandidates): void {
                $query->whereHas('contract')
                    ->orWhereIn('id', $usersWithStaleCandidates);
            })
            ->get();

        foreach ($users as $user) {
            $userViolations = $this->validateRulesForUser($user, $startDate, $endDate);
            $violations = $violations->concat($userViolations);
        }

        return $violations;
    }

    public function getCompensationDataForUser(User $user): array
    {
        return [
            'openCompensations' => $this->compensationDayOffRepository
                ->getOpenForUser($user->id),
            'grantedCompensations' => $this->compensationDayOffRepository
                ->getGrantedForUser($user->id),
            'unprocessedViolations' => $this->shiftRuleViolationRepository
                ->getUnprocessedViolationsForUser($user->id),
            'compensationPeriod' => $user->activeWorkContract()?->compensation_period ?? 0,
        ];
    }

    public function getAvailableRuleTypes(): array
    {
        return [
            'maxWorkingHoursOnDay',
            'maxConsecWorkingDays',
            'weeklyMaxHours',
            'restTimeBeforeWorkday',
            'restTimeBeforeHoliday',
            'restTimeBetweenShiftGroups',
            'halfDayOffConflict',
            'halfDayOffOnSpecialDay',
            'minDaysBeforeCommit',
            'workOnSunday',
            'workOnHoliday',
        ];
    }

    /**
     * Regeltypen ohne Zahlenwert (individual_number_value wird ignoriert und mit 0 gespeichert).
     *
     * @return array<int, string>
     */
    public static function ruleTypesWithoutValue(): array
    {
        return ['halfDayOffOnSpecialDay', 'workOnSunday', 'workOnHoliday'];
    }

    public function mapViolationsToArray(Collection $violations): Collection
    {
        return $violations->map(function ($violation) {
            return [
                'id' => $violation->id,
                'rule_name' => $violation->shiftRule?->name,
                'user_name' => $violation->user->first_name . ' ' . $violation->user->last_name,
                'violation_date' => $violation->violation_date,
                'message' => $violation->getViolationMessage(),
                'severity' => $violation->severity,
                'warning_color' => $violation->getWarningColor(),
                'violation_data' => $violation->violation_data ?? null,
                'status' => $violation->status ?? null,
                'is_manual' => $violation->is_manual ?? false,
                'reason' => $violation->reason,
                'compensation_days' => $violation->compensation_days,
                'compensation_deadline' => $violation->compensation_deadline,
                'compensation_reason' => $violation->compensation_reason,
                'ignore_reason' => $violation->ignore_reason,
                'resolved_at' => $violation->resolved_at?->toIso8601String(),
                'updated_at' => $violation->updated_at?->toIso8601String(),
                'resolved_by_user' => $violation->resolvedByUser ? [
                    'first_name' => $violation->resolvedByUser->first_name,
                    'last_name' => $violation->resolvedByUser->last_name,
                ] : null,
                'shift_rule' => $violation->shiftRule ? [
                    'name' => $violation->shiftRule->name,
                    'trigger_type' => $violation->shiftRule->trigger_type,
                    'description' => $violation->shiftRule->description,
                    'warning_color' => $violation->shiftRule->warning_color,
                    'default_compensation_days' => $violation->shiftRule->default_compensation_days,
                    'default_compensation_deadline_days' => $violation->shiftRule->default_compensation_deadline_days,
                ] : null,
                'created_by_user' => $violation->createdByUser ? [
                    'first_name' => $violation->createdByUser->first_name,
                    'last_name' => $violation->createdByUser->last_name,
                ] : null,
            ];
        })->values();
    }
}

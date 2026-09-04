<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Models\ShiftRule;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\CompensationDayOffRepository;
use Artwork\Modules\Shift\RuleChecks\AbstractRuleCheck;
use Artwork\Modules\Shift\RuleChecks\ShiftRuleCheckContext;
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
        private readonly ShiftRuleRevalidationService $revalidationService,
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

        // Neue Regel: Personen der zugeordneten Verträge neu prüfen (auch jenseits der 14-Tage-Cron-Sicht).
        $this->revalidationService->revalidateForContracts($contractIds ?? []);

        return $rule;
    }

    public function updateRule(
        ShiftRule $rule,
        array $attributes,
        ?array $contractIds = null,
        ?array $userIds = null
    ): ShiftRule {
        $previousContractIds = $rule->contracts()->pluck('user_contracts.id')->all();

        $this->shiftRuleRepository->update($rule, $attributes);

        $rule->contracts()->sync($contractIds ?? []);
        $rule->usersToNotify()->sync($userIds ?? []);

        // Geänderter Wert/Status oder geänderte Vertragszuordnung: alte UND neue Verträge neu prüfen.
        $this->revalidationService->revalidateForContracts(array_merge($previousContractIds, $contractIds ?? []));

        return $rule;
    }

    /**
     * Regel löschen (Soft Delete — die FK-Kaskade greift daher NICHT): ihre aktiven automatischen
     * Verstöße werden hier entfernt, bearbeitete/ignorierte und manuelle bleiben als Historie.
     * Die betroffenen Personen werden anschließend neu geprüft.
     */
    public function deleteRule(ShiftRule $rule): bool
    {
        $contractIds = $rule->contracts()->pluck('user_contracts.id')->all();

        ShiftRuleViolation::query()
            ->where('shift_rule_id', $rule->id)
            ->where('status', 'active')
            ->where('is_manual', false)
            ->get()
            ->each->delete();

        $deleted = $this->shiftRuleRepository->delete($rule);

        $this->revalidationService->revalidateForContracts($contractIds);

        return $deleted;
    }

    public function syncContractsForRule(ShiftRule $rule, array $contractIds): void
    {
        $previousContractIds = $rule->contracts()->pluck('user_contracts.id')->all();

        $rule->contracts()->sync($contractIds);

        $this->revalidationService->revalidateForContracts(array_merge($previousContractIds, $contractIds));
    }

    public function syncUsersForRule(ShiftRule $rule, array $userIds): void
    {
        $rule->usersToNotify()->sync($userIds);
    }

    public function updateContractAssignments(UserContract $contract, array $ruleIds): void
    {
        $contract->shiftRules()->sync($ruleIds);

        $this->revalidationService->revalidateForContracts([$contract->id]);
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
     *
     * Batching: Schichten (mit Pivot-Zeiten), Individualzeiten, Ersatzfreitage und Sondertage werden
     * einmal je Person und Lauf in einen ShiftRuleCheckContext geladen (Rand: Rückblick für "Tage in
     * Folge", Wochenfenster, Vortag für Ruhezeiten) und an alle Checks gereicht — die Zahl der Abfragen
     * je Person ist damit unabhängig von der Tagesanzahl.
     */
    public function validateRulesForUser(
        User $user,
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        $violations = collect();

        $activeContract = $user->activeWorkContract();
        $rules = $activeContract ? $this->getRulesForContract($activeContract) : collect();

        $context = ShiftRuleCheckContext::forRange(
            $user,
            $startDate,
            $endDate,
            $this->lookbackDaysFor($rules),
            7
        );

        // Je Regel das Fenster, das der Check tatsächlich beurteilt hat (Standard: der Zeitraum).
        $coveredRangeByRule = [];
        $checksWithContext = [];

        try {
            foreach ($rules as $rule) {
                if (!$this->ruleCheckFactory->has($rule->trigger_type)) {
                    // Unbekannter Regeltyp (z. B. Altbestand): weder prüfen noch aufräumen.
                    $coveredRangeByRule[$rule->id] = null;
                    continue;
                }

                $check = $this->ruleCheckFactory->create($rule->trigger_type);
                if ($check instanceof AbstractRuleCheck) {
                    $check->setContext($context);
                    $checksWithContext[] = $check;
                }

                $ruleViolations = $check->check($rule, $user, $startDate, $endDate);
                $violations = $violations->concat($ruleViolations);

                $coveredRangeByRule[$rule->id] = $check instanceof AbstractRuleCheck
                    ? $check->getCoveredRange($rule, $startDate, $endDate)
                    : [$startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()];
            }
        } finally {
            foreach ($checksWithContext as $check) {
                $check->setContext(null);
            }
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
     * Rückblick des Kontexts: "Tage in Folge" zählt eine laufende Serie vor dem Prüfzeitraum mit —
     * Regelwert + 1 Tage (mind. 7); längere Serien fallen im Check auf Direktabfragen zurück.
     *
     * @param Collection<int, ShiftRule> $rules
     */
    private function lookbackDaysFor(Collection $rules): int
    {
        $lookback = 7;
        foreach ($rules as $rule) {
            if ($rule->trigger_type === 'maxConsecWorkingDays') {
                $lookback = max($lookback, (int) ceil((float) $rule->individual_number_value) + 1);
            }
        }

        return min($lookback, 62);
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
     * Manuelle Verstöße (auch ohne Regel) werden nie angefasst.
     *
     * Eine Abfrage über die Vereinigung aller Fenster, Zuordnung je Regel im Speicher.
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
        $overallFrom = $startDate->copy()->startOfDay();
        $overallTo = $endDate->copy()->startOfDay();
        foreach ($coveredRangeByRule as $range) {
            if ($range === null) {
                continue;
            }
            $overallFrom = $overallFrom->min($range[0]);
            $overallTo = $overallTo->max($range[1]);
        }

        $candidates = ShiftRuleViolation::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('is_manual', false)
            ->whereNotNull('shift_rule_id')
            ->whereNull('parent_violation_id')
            ->whereDoesntHave('compensationDayOffs')
            ->when($keptViolationIds !== [], fn ($q) => $q->whereNotIn('id', $keptViolationIds))
            ->whereBetween('violation_date', [$overallFrom->toDateString(), $overallTo->toDateString()])
            ->get();

        $runFrom = $startDate->toDateString();
        $runTo = $endDate->toDateString();

        foreach ($candidates as $violation) {
            $dateKey = $violation->violation_date->toDateString();

            if (array_key_exists($violation->shift_rule_id, $coveredRangeByRule)) {
                // Regel wurde geprüft: nur im abgedeckten Fenster aufräumen.
                $range = $coveredRangeByRule[$violation->shift_rule_id];
                if ($range === null) {
                    continue;
                }
                if ($dateKey >= $range[0]->toDateString() && $dateKey <= $range[1]->toDateString()) {
                    $violation->delete();
                }
                continue;
            }

            // Regel ist der Person nicht (mehr) zugeordnet: im gesamten Zeitraum aufräumen.
            if ($dateKey >= $runFrom && $dateKey <= $runTo) {
                $violation->delete();
            }
        }
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
            'overtimeDeadline',
            'minFreeSundaysPerSeasonHalf',
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

    /**
     * Regeltypen mit OPTIONALEM Zahlenwert: leer/0 = Zielwert aus dem Vertrag (z. B. freie Sonntage je
     * Spielzeithälfte aus free_sundays_sat_mon_per_half).
     *
     * @return array<int, string>
     */
    public static function ruleTypesWithOptionalValue(): array
    {
        return ['minFreeSundaysPerSeasonHalf'];
    }

    public function mapViolationsToArray(Collection $violations): Collection
    {
        return $violations->map(function ($violation) {
            return [
                'id' => $violation->id,
                'rule_name' => $violation->shiftRule?->name,
                // Manuelle Verstöße ohne Regel tragen einen eigenen Titel
                'title' => $violation->title,
                'display_name' => $violation->getDisplayName(),
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

<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * „Ersatz suchen" nach einer Absage: schlägt freie Personen mit passender
 * Funktion vor und tauscht die abgesagte Zuweisung in einer Transaktion
 * gegen die Ersatzperson (bestehende Remove-/Assign-Pfade inkl. Notifications).
 */
class ShiftReplacementService
{
    public const MAX_CANDIDATES = 30;

    public function __construct(
        private readonly ShiftWorkerService $shiftWorkerService,
        protected AuthManager $auth
    ) {
    }

    /**
     * Kandidat*innen für den abgesagten Platz.
     *
     * Regeln: gleiches Gewerk (oder universell einsetzbares Gewerk) und Funktion des
     * abgesagten Platzes, nicht bereits in der Schicht, am Tag weder im Urlaub noch
     * „nicht verfügbar" (ganztags oder Zeitfenster überlappend) und ohne zeitliche
     * Überschneidung mit anderen Zuweisungen (effektive Pivot-Zeiten, über Mitternacht).
     * Sortierung: Stundensaldo aufsteigend (unbekannt zuletzt), dann Name.
     *
     * Query-Budget ist konstant (keine N+1): je Tabelle eine Abfrage für alle Kandidat*innen.
     *
     * @return array{slot: array<string, mixed>, candidates: list<array<string, mixed>>}
     */
    public function candidatesFor(Shift $shift, ShiftWorker $declined, bool $showHours = true): array
    {
        $shift->loadMissing('craft');
        $craft = $shift->craft;
        $qualificationId = (int) $declined->shift_qualification_id;
        $qualification = $qualificationId > 0 ? ShiftQualification::query()->find($qualificationId) : null;

        [$startDate, $endDate, $slotStart, $slotEnd] = $this->resolveSlotWindow($shift, $declined);

        $universalCraftIds = Craft::query()
            ->where('universally_applicable', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allowedCraftIds = array_values(array_unique(array_merge(
            $craft ? [(int) $craft->id] : [],
            $universalCraftIds
        )));

        $users = $this->candidateQuery(User::query(), $allowedCraftIds, $qualificationId)
            ->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])
            ->get();

        $freelancers = $this->candidateQuery(Freelancer::query(), $allowedCraftIds, $qualificationId)
            ->get();

        // Bereits eingeplante Personen (inkl. der abgesagten) fallen raus.
        $assignedKeys = ShiftWorker::withoutTrashed()
            ->where('shift_id', $shift->id)
            ->get(['employable_type', 'employable_id'])
            ->map(fn (ShiftWorker $pivot) => $this->key($pivot->employable_type, (int) $pivot->employable_id))
            ->flip();

        $candidates = collect();
        foreach ($users as $user) {
            if (!$assignedKeys->has($this->key(User::class, (int) $user->id))) {
                $candidates->push($user);
            }
        }
        foreach ($freelancers as $freelancer) {
            if (!$assignedKeys->has($this->key(Freelancer::class, (int) $freelancer->id))) {
                $candidates->push($freelancer);
            }
        }

        $userIds = $candidates->filter(fn ($c) => $c instanceof User)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $freelancerIds = $candidates->filter(fn ($c) => $c instanceof Freelancer)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $craftAbbreviations = $this->craftAbbreviationsFor($userIds, $freelancerIds, $craft, $universalCraftIds);
        $vacations = $this->vacationsFor($userIds, $freelancerIds, $startDate, $endDate);
        $otherPivots = $this->otherAssignmentsFor($shift, $userIds, $freelancerIds, $startDate, $endDate);
        $dayServices = $this->dayServicesFor($userIds, $freelancerIds, $startDate, $endDate);
        $individualTimes = $this->individualTimesFor($userIds, $freelancerIds, $startDate, $endDate);

        $result = [];
        foreach ($candidates as $candidate) {
            $type = $candidate instanceof User ? User::class : Freelancer::class;
            $key = $this->key($type, (int) $candidate->id);

            $hints = [];

            // (b) Urlaub / nicht verfügbar am Tag → raus; „Frei laut Planung" nur als Hinweis
            $blockedByVacation = false;
            foreach ($vacations->get($key, collect()) as $vacation) {
                if (!$this->vacationOverlaps($vacation, $startDate, $endDate, $slotStart, $slotEnd)) {
                    continue;
                }
                $vacationType = $vacation->type instanceof VacationType ? $vacation->type->value : (string) $vacation->type;
                if ($vacationType === VacationType::FREE_WORK->value) {
                    $hints[] = __('Free day according to planning');
                    continue;
                }
                $blockedByVacation = true;
                break;
            }
            if ($blockedByVacation) {
                continue;
            }

            // (c) Überschneidung mit anderen Zuweisungen → raus; sonst Tages-Hinweis
            $collides = false;
            foreach ($otherPivots->get($key, collect()) as $pivot) {
                $window = $this->pivotWindow($pivot);
                if ($window === null) {
                    continue;
                }
                [$otherStart, $otherEnd] = $window;
                if ($otherStart->lessThan($slotEnd) && $otherEnd->greaterThan($slotStart)) {
                    $collides = true;
                    break;
                }
                if ($otherStart->toDateString() <= $endDate && $otherEnd->toDateString() >= $startDate) {
                    $hints[] = __('Already has a shift today: :label', [
                        'label' => trim(($pivot->shift?->craft?->abbreviation ?? '') . ' '
                            . $otherStart->format('H:i') . '–' . $otherEnd->format('H:i')),
                    ]);
                }
            }
            if ($collides) {
                continue;
            }

            foreach ($dayServices->get($key, collect()) as $dayServiceName) {
                $hints[] = __('Day service: :name', ['name' => $dayServiceName]);
            }

            foreach ($individualTimes->get($key, collect()) as $individualTime) {
                $hints[] = $this->individualTimeHint($individualTime, $slotStart, $slotEnd);
            }

            $balanceMinutes = $candidate instanceof User ? (int) ($candidate->work_time_balance ?? 0) : null;
            $fullName = $candidate instanceof User
                ? $candidate->getFullNameAttribute()
                : $candidate->getNameAttribute();

            $result[] = [
                'id' => (int) $candidate->id,
                'type' => $candidate instanceof User ? 'user' : 'freelancer',
                'full_name' => $fullName,
                'profile_photo_url' => $candidate->profile_photo_url,
                'qualification_id' => $qualificationId,
                'qualification_name' => $qualification?->name,
                'craft_abbreviation' => $craftAbbreviations[$key] ?? ($craft?->abbreviation ?? ''),
                'balance_minutes' => $showHours ? $balanceMinutes : null,
                'balance_label' => $showHours && $balanceMinutes !== null
                    ? WorkTimeCalculationService::formatSignedHours($balanceMinutes)
                    : null,
                'hints' => array_values(array_unique($hints)),
                // Sortierschlüssel (nicht Teil der API-Zusage)
                '_sort_balance' => $balanceMinutes ?? PHP_INT_MAX,
            ];
        }

        usort($result, static function (array $a, array $b): int {
            return [$a['_sort_balance'], mb_strtolower($a['full_name'])]
                <=> [$b['_sort_balance'], mb_strtolower($b['full_name'])];
        });

        $result = array_map(static function (array $row): array {
            unset($row['_sort_balance']);
            return $row;
        }, array_slice($result, 0, self::MAX_CANDIDATES));

        return [
            'slot' => [
                'shift_worker_id' => (int) $declined->id,
                'declined_name' => $this->workerName($declined),
                'declined_type' => $this->typeSlug($declined->employable_type),
                'declined_id' => (int) $declined->employable_id,
                'confirmation_comment' => $declined->confirmation_comment,
                'confirmation_at' => $declined->confirmation_at?->toIso8601String(),
                'date' => Carbon::parse($startDate)->format('d.m.Y'),
                'start' => $slotStart->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'craft_name' => $craft?->name,
                'craft_abbreviation' => $craft?->abbreviation,
                'qualification_id' => $qualificationId,
                'qualification_name' => $qualification?->name,
            ],
            'candidates' => $result,
        ];
    }

    /**
     * Tauscht die abgesagte Zuweisung gegen die Ersatzperson — atomar über die
     * bestehenden Remove-/Assign-Pfade (Verlauf, Festschreibungs-Änderungen,
     * Notifications „entfernt"/„zugewiesen", Konfliktprüfungen).
     */
    public function replace(
        Shift $shift,
        ShiftWorker $declined,
        Employable $replacement,
        int $shiftQualificationId,
        string $craftAbbreviation,
        NotificationService $notificationService,
        VacationConflictService $vacationConflictService,
        AvailabilityConflictService $availabilityConflictService,
        ChangeService $changeService
    ): ShiftWorker {
        $declined->setRelation('shift', $shift);
        $declinedName = $this->workerName($declined);

        $isOverbooked = (bool) $declined->is_overbooked
            && app(\App\Settings\ShiftSettings::class)->allow_shift_overbooking;

        return DB::transaction(function () use (
            $shift,
            $declined,
            $declinedName,
            $replacement,
            $shiftQualificationId,
            $craftAbbreviation,
            $isOverbooked,
            $notificationService,
            $vacationConflictService,
            $availabilityConflictService,
            $changeService
        ): ShiftWorker {
            $this->shiftWorkerService->removeFromShift(
                $declined,
                true,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService
            );

            $pivot = $this->shiftWorkerService->assignToShift(
                $shift,
                $replacement,
                $shiftQualificationId,
                $craftAbbreviation,
                $notificationService,
                $vacationConflictService,
                $availabilityConflictService,
                $changeService,
                null,
                $isOverbooked
            );

            // ShiftWorker ist ein Pivot-Modell (incrementing = false): nach save() fehlt die id →
            // frischen Satz laden, damit Antwort/Verlauf die Pivot-ID kennen.
            $fresh = ShiftWorker::query()
                ->where('shift_id', $shift->id)
                ->where('employable_type', $pivot->employable_type)
                ->where('employable_id', $pivot->employable_id)
                ->orderByDesc('id')
                ->first();
            if ($fresh !== null) {
                $fresh->setRelation('employable', $replacement);
                $pivot = $fresh;
            }

            $this->logReplacementActivity($shift, $pivot, $declinedName);

            return $pivot;
        });
    }

    private function logReplacementActivity(Shift $shift, ShiftWorker $pivot, string $declinedName): void
    {
        $shift->loadMissing('craft');
        $pivot->loadMissing('shiftQualification');
        $replacementName = $this->workerName($pivot);

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event('replaced')
            ->tap(function ($activity) use ($shift, $pivot, $declinedName, $replacementName): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => '{0} was replaced by {1} as {2} for {3} ({4})',
                    'translation_key_placeholder_values' => [
                        $declinedName,
                        $replacementName,
                        $pivot->shiftQualification?->name ?? '',
                        $shift->craft?->name ?? '',
                        $pivot->craft_abbreviation ?? $shift->craft?->abbreviation ?? '',
                    ],
                    'context' => $shift->is_committed
                        ? 'post_commit'
                        : ($shift->in_workflow ? 'in_workflow' : 'normal'),
                    'shift_id' => $shift->id,
                    'craft_id' => $shift->craft_id,
                    'shift_snapshot' => $shift->toActivitySnapshot(),
                ]);
            })
            ->log('Worker replaced in shift');
    }

    /* ---------------- Kandidat*innen-Abfragen ---------------- */

    /**
     * @param list<int> $allowedCraftIds
     */
    private function candidateQuery(Builder $query, array $allowedCraftIds, int $qualificationId): Builder
    {
        return $query
            ->where('can_work_shifts', true)
            ->whereHas('assignedCrafts', function (Builder $q) use ($allowedCraftIds): void {
                $q->whereIn('crafts.id', $allowedCraftIds);
            })
            ->whereHas('shiftQualifications', function (Builder $q) use ($qualificationId, $allowedCraftIds): void {
                $q->where('shift_qualifications.id', $qualificationId)
                    ->where(function (Builder $qq) use ($allowedCraftIds): void {
                        $qq->whereIn('shift_qualifiables.craft_id', $allowedCraftIds)
                            ->orWhereNull('shift_qualifiables.craft_id');
                    });
            });
    }

    /**
     * Kürzel für die Zuweisung: Gewerk der Schicht, sonst das universelle Gewerk der Person.
     *
     * @param list<int> $userIds
     * @param list<int> $freelancerIds
     * @param list<int> $universalCraftIds
     * @return array<string, string>
     */
    private function craftAbbreviationsFor(array $userIds, array $freelancerIds, ?Craft $craft, array $universalCraftIds): array
    {
        if ($userIds === [] && $freelancerIds === []) {
            return [];
        }

        $rows = DB::table('craftables')
            ->join('crafts', 'crafts.id', '=', 'craftables.craft_id')
            ->where(function ($q) use ($userIds, $freelancerIds): void {
                $this->applyMorphFilter($q, 'craftables.craftable_type', 'craftables.craftable_id', $userIds, $freelancerIds);
            })
            ->get(['craftables.craftable_type', 'craftables.craftable_id', 'crafts.id as craft_id', 'crafts.abbreviation']);

        $result = [];
        foreach ($rows as $row) {
            $key = $this->key($row->craftable_type, (int) $row->craftable_id);
            if ($craft && (int) $row->craft_id === (int) $craft->id) {
                $result[$key] = (string) $row->abbreviation;
                continue;
            }
            if (!isset($result[$key]) && in_array((int) $row->craft_id, $universalCraftIds, true)) {
                $result[$key] = (string) $row->abbreviation;
            }
        }

        return $result;
    }

    /**
     * @return Collection<string, Collection<int, Vacation>>
     */
    private function vacationsFor(array $userIds, array $freelancerIds, string $startDate, string $endDate): Collection
    {
        if ($userIds === [] && $freelancerIds === []) {
            return collect();
        }

        return Vacation::query()
            ->without(['series', 'conflicts'])
            ->whereBetween('date', [$startDate, $endDate])
            ->where('type', '!=', VacationType::AVAILABLE->value)
            ->where(function ($q) use ($userIds, $freelancerIds): void {
                $this->applyMorphFilter($q, 'vacationer_type', 'vacationer_id', $userIds, $freelancerIds);
            })
            ->get()
            ->groupBy(fn (Vacation $vacation) => $this->key($vacation->vacationer_type, (int) $vacation->vacationer_id));
    }

    /**
     * @return Collection<string, Collection<int, ShiftWorker>>
     */
    private function otherAssignmentsFor(
        Shift $shift,
        array $userIds,
        array $freelancerIds,
        string $startDate,
        string $endDate
    ): Collection {
        if ($userIds === [] && $freelancerIds === []) {
            return collect();
        }

        $scopeStart = Carbon::parse($startDate)->subDay()->toDateString();
        $scopeEnd = Carbon::parse($endDate)->addDay()->toDateString();

        return ShiftWorker::withoutTrashed()
            ->with(['shift.craft' => fn ($q) => $q->without('craftShiftPlaner')])
            ->where('shift_id', '!=', $shift->id)
            ->whereHas('shift', function ($q) use ($scopeStart, $scopeEnd): void {
                $q->where('start_date', '<=', $scopeEnd)
                    ->where('end_date', '>=', $scopeStart);
            })
            ->where(function ($q) use ($userIds, $freelancerIds): void {
                $this->applyMorphFilter($q, 'employable_type', 'employable_id', $userIds, $freelancerIds);
            })
            ->get()
            ->groupBy(fn (ShiftWorker $pivot) => $this->key($pivot->employable_type, (int) $pivot->employable_id));
    }

    /**
     * @return Collection<string, Collection<int, string>>
     */
    private function dayServicesFor(array $userIds, array $freelancerIds, string $startDate, string $endDate): Collection
    {
        if ($userIds === [] && $freelancerIds === []) {
            return collect();
        }

        return DB::table('day_serviceables')
            ->join('day_services', 'day_services.id', '=', 'day_serviceables.day_service_id')
            ->whereBetween('day_serviceables.date', [$startDate, $endDate])
            ->where(function ($q) use ($userIds, $freelancerIds): void {
                $this->applyMorphFilter(
                    $q,
                    'day_serviceables.day_serviceable_type',
                    'day_serviceables.day_serviceable_id',
                    $userIds,
                    $freelancerIds
                );
            })
            ->get(['day_serviceables.day_serviceable_type', 'day_serviceables.day_serviceable_id', 'day_services.name'])
            ->groupBy(fn ($row) => $this->key($row->day_serviceable_type, (int) $row->day_serviceable_id))
            ->map(fn (Collection $rows) => $rows->pluck('name'));
    }

    /**
     * @return Collection<string, Collection<int, IndividualTime>>
     */
    private function individualTimesFor(array $userIds, array $freelancerIds, string $startDate, string $endDate): Collection
    {
        if ($userIds === [] && $freelancerIds === []) {
            return collect();
        }

        return IndividualTime::query()
            ->individualByDateRange($startDate, $endDate)
            ->where(function ($q) use ($userIds, $freelancerIds): void {
                $this->applyMorphFilter($q, 'timeable_type', 'timeable_id', $userIds, $freelancerIds);
            })
            ->get()
            ->groupBy(fn (IndividualTime $time) => $this->key($time->timeable_type, (int) $time->timeable_id));
    }

    private function applyMorphFilter($query, string $typeColumn, string $idColumn, array $userIds, array $freelancerIds): void
    {
        if ($userIds !== []) {
            $query->orWhere(function ($q) use ($typeColumn, $idColumn, $userIds): void {
                $q->where($typeColumn, User::class)->whereIn($idColumn, $userIds);
            });
        }
        if ($freelancerIds !== []) {
            $query->orWhere(function ($q) use ($typeColumn, $idColumn, $freelancerIds): void {
                $q->where($typeColumn, Freelancer::class)->whereIn($idColumn, $freelancerIds);
            });
        }
        if ($userIds === [] && $freelancerIds === []) {
            $query->whereRaw('1 = 0');
        }
    }

    /* ---------------- Zeitfenster ---------------- */

    /**
     * Effektives Fenster des abgesagten Platzes: Pivot-Zeiten vor Schichtzeiten.
     *
     * @return array{0: string, 1: string, 2: Carbon, 3: Carbon}
     */
    private function resolveSlotWindow(Shift $shift, ShiftWorker $pivot): array
    {
        $shiftStartDate = $shift->start_date ? Carbon::parse($shift->start_date)->toDateString() : now()->toDateString();
        $shiftEndDate = $shift->end_date ? Carbon::parse($shift->end_date)->toDateString() : $shiftStartDate;

        $startDate = $pivot->start_date ? Carbon::parse($pivot->start_date)->toDateString() : $shiftStartDate;
        $endDate = $pivot->end_date ? Carbon::parse($pivot->end_date)->toDateString() : $shiftEndDate;
        $startTime = $this->normalizeTime($pivot->start_time) ?? $this->normalizeTime($shift->start) ?? '00:00';
        $endTime = $this->normalizeTime($pivot->end_time) ?? $this->normalizeTime($shift->end) ?? '23:59';

        $slotStart = Carbon::parse($startDate . ' ' . $startTime);
        $slotEnd = Carbon::parse($endDate . ' ' . $endTime);
        if ($slotEnd->lessThanOrEqualTo($slotStart)) {
            $slotEnd->addDay();
        }

        return [$startDate, $endDate, $slotStart, $slotEnd];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function pivotWindow(ShiftWorker $pivot): ?array
    {
        $shift = $pivot->shift;
        if (!$shift) {
            return null;
        }

        $startDate = $pivot->start_date ?? $shift->start_date;
        $endDate = $pivot->end_date ?? $shift->end_date ?? $startDate;
        $startTime = $this->normalizeTime($pivot->start_time) ?? $this->normalizeTime($shift->start);
        $endTime = $this->normalizeTime($pivot->end_time) ?? $this->normalizeTime($shift->end);

        if (!$startDate || !$startTime || !$endTime) {
            return null;
        }

        $start = Carbon::parse(Carbon::parse($startDate)->toDateString() . ' ' . $startTime);
        $end = Carbon::parse(Carbon::parse($endDate)->toDateString() . ' ' . $endTime);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    private function vacationOverlaps(
        Vacation $vacation,
        string $startDate,
        string $endDate,
        Carbon $slotStart,
        Carbon $slotEnd
    ): bool {
        $vacationDate = Carbon::parse($vacation->date)->toDateString();
        if ($vacationDate < $startDate || $vacationDate > $endDate) {
            return false;
        }

        if ($vacation->full_day || !$vacation->start_time || !$vacation->end_time) {
            return true;
        }

        $vacationStart = Carbon::parse($vacationDate . ' ' . $this->normalizeTime($vacation->start_time));
        $vacationEnd = Carbon::parse($vacationDate . ' ' . $this->normalizeTime($vacation->end_time));
        if ($vacationEnd->lessThanOrEqualTo($vacationStart)) {
            $vacationEnd->addDay();
        }

        return $vacationStart->lessThan($slotEnd) && $vacationEnd->greaterThan($slotStart);
    }

    private function individualTimeHint(IndividualTime $time, Carbon $slotStart, Carbon $slotEnd): string
    {
        $title = trim((string) ($time->title ?? ''));

        if ($time->full_day || !$time->start_time || !$time->end_time) {
            return __('Individual time: :label', ['label' => trim($title . ' ' . __('all day'))]);
        }

        $start = $this->normalizeTime($time->start_time);
        $end = $this->normalizeTime($time->end_time);
        $label = trim($title . ' ' . $start . '–' . $end);

        $timeStart = Carbon::parse(Carbon::parse($time->start_date)->toDateString() . ' ' . $start);
        $timeEnd = Carbon::parse(Carbon::parse($time->end_date ?? $time->start_date)->toDateString() . ' ' . $end);
        if ($timeEnd->lessThanOrEqualTo($timeStart)) {
            $timeEnd->addDay();
        }

        if ($timeStart->lessThan($slotEnd) && $timeEnd->greaterThan($slotStart)) {
            $label .= ' (' . __('overlaps') . ')';
        }

        return __('Individual time: :label', ['label' => $label]);
    }

    private function normalizeTime(mixed $time): ?string
    {
        if ($time instanceof DateTimeInterface) {
            return $time->format('H:i');
        }

        if (is_string($time) && $time !== '') {
            return substr($time, 0, 5);
        }

        return null;
    }

    /* ---------------- Helfer ---------------- */

    private function key(string $type, int $id): string
    {
        return $type . '#' . $id;
    }

    private function typeSlug(?string $employableType): string
    {
        return match ($employableType) {
            User::class => 'user',
            Freelancer::class => 'freelancer',
            default => 'service_provider',
        };
    }

    private function workerName(ShiftWorker $pivot): string
    {
        $employable = $pivot->relationLoaded('employable') ? $pivot->getRelation('employable') : $pivot->employable;

        return $employable?->full_name
            ?? $employable?->name
            ?? '';
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\DTOs\AiPersonRefData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteConflictData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteItemData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteSummaryData;
use Artwork\Modules\AiAssistant\Support\AiErrors;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftAssignmentPreflightService;
use Artwork\Modules\Shift\Support\SafeBroadcast;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Enums\Vacation as VacationType;
use Artwork\Modules\Vacation\Events\WorkerAvailabilityChanged;
use Artwork\Modules\Vacation\Https\Requests\CreateVacationRequest;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Vacation\Services\VacationSeriesService;
use Artwork\Modules\Vacation\Services\VacationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * `POST /api/v1/ai/absences` — Werkzeug `create_absence` (ai-scenarios-4 S11). Auch von
 * `replace_in_shifts` (`mark_absent`) genutzt.
 *
 * Angelegt wird je Tag ueber `VacationService::create()` wie im Verfuegbarkeitskalender (ohne
 * Serie): `vacation` = Planungsstatus `OFF_WORK`, `not_available` = `NOT_AVAILABLE`. Der Dienst
 * prueft dabei Konflikte mit festgeschriebenen Schichten (`VacationConflictService`) und
 * benachrichtigt wie die Oberflaeche. Weil das synchron Benachrichtigungen erzeugt, ist die
 * Vorschau **nicht** die echte Ausfuehrung: sie zaehlt die Schichten der Person an den Tagen
 * (Hinweis `has_shifts`, kein Blocker) und meldet vorhandene Abwesenheiten (`already_absent`).
 *
 * Rechte wie `VacationController::store()`: fuer sich selbst immer, fuer andere „Verfuegbarkeiten
 * verwalten" oder „Schichten planen" (bei Freelancern/Dienstleistern auch die Worker-Manager-Rechte)
 * oder Admin — sonst 403 `no_availability_access`. Undo loescht die angelegten Eintraege.
 */
class AiAbsenceWriteService
{
    public const TOOL_CREATE = 'create_absence';

    public const TYPE_VACATION = 'vacation';

    public const TYPE_NOT_AVAILABLE = 'not_available';

    public const TYPES = [self::TYPE_VACATION, self::TYPE_NOT_AVAILABLE];

    public function __construct(
        private readonly VacationService $vacationService,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
    ) {
    }

    /**
     * @param list<string> $days
     * @param array{start_time: string, end_time: string}|null $times
     */
    public function create(
        User $actor,
        User|Freelancer|ServiceProvider $person,
        array $days,
        string $type,
        ?array $times,
        ?string $comment,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        if (!self::mayManage($actor, $person)) {
            AiErrors::abort(AiErrors::NO_AVAILABILITY_ACCESS);
        }

        $items = $this->previewItems($person, $days, $type, $times);
        $summary = $this->summarize($items);

        if ($intent->dryRun) {
            return new AiWritePreviewData(true, self::TOOL_CREATE, $items, $summary);
        }

        $this->actionGuard->assertExecutable($actor, (string) $intent->actionId, self::TOOL_CREATE, $intent->args);

        DB::beginTransaction();

        try {
            $vacations = $this->perform($actor, $person, $days, $type, $times, $comment);
            $batch = $this->batchRecorder->record($actor, self::TOOL_CREATE, $this->batchItems($vacations));
            $this->actionGuard->complete($actor, (string) $intent->actionId, $batch?->id);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }

        $this->broadcast($person);

        return new AiWriteResultData(
            dry_run: false,
            tool: self::TOOL_CREATE,
            batch_id: $batch?->id,
            undo_until: $batch?->undoUntil,
            ids: array_map(static fn (Vacation $vacation): int => (int) $vacation->id, $vacations),
            items: $items,
            links: [],
            summary: $summary,
        );
    }

    public static function mayManage(User $actor, User|Freelancer|ServiceProvider $person): bool
    {
        if ($person instanceof User && (int) $person->id === (int) $actor->id) {
            return true;
        }

        if ($actor->hasRole(RoleEnum::ARTWORK_ADMIN->value)) {
            return true;
        }

        $permissions = [PermissionEnum::AVAILABILITY_MANAGEMENT->value, PermissionEnum::SHIFT_PLANNER->value];
        if (!$person instanceof User) {
            $permissions[] = PermissionEnum::MA_MANAGER->value;
            $permissions[] = PermissionEnum::EXTERNAL_MANAGER->value;
        }

        foreach ($permissions as $permission) {
            if ($actor->checkPermissionTo($permission, 'web')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ein Eintrag je Tag; Hinweise auf Schichten an dem Tag und vorhandene Abwesenheiten.
     *
     * @param list<string> $days
     * @param array{start_time: string, end_time: string}|null $times
     * @return list<AiWriteItemData>
     */
    public function previewItems(
        User|Freelancer|ServiceProvider $person,
        array $days,
        string $type,
        ?array $times
    ): array {
        $ref = new AiPersonRefData(
            AiPeopleService::typeOf($person),
            (int) $person->getKey(),
            (string) AiText::truncate(AiPeopleService::nameOf($person)),
        );
        $timezone = (string) config('app.timezone');
        $typeLabel = $type === self::TYPE_VACATION ? 'Urlaub/arbeitsfrei' : 'Nicht verfuegbar';
        $items = [];

        foreach ($days as $day) {
            [$start, $end] = $this->window($day, $times);
            $conflicts = [];
            $shifts = $this->shiftsWithin($person, $start, $end);

            if ($shifts > 0) {
                $conflicts[] = new AiWriteConflictData(
                    AiWriteConflictData::HAS_SHIFTS,
                    $ref->name . " hat an diesem Tag {$shifts} Schicht(en); sie bleiben bestehen.",
                    null
                );
            }

            if ($person->vacations()->whereDate('date', $day)->exists()) {
                $conflicts[] = new AiWriteConflictData(
                    AiWriteConflictData::ALREADY_ABSENT,
                    'An diesem Tag ist bereits eine Abwesenheit eingetragen.',
                    null
                );
            }

            $items[] = new AiWriteItemData(
                action: 'create',
                id: null,
                label: AiText::truncate($ref->name . ': ' . $typeLabel . ' ' . Carbon::parse($day)->format('d.m.Y')
                    . ($times === null ? ' (ganztaegig)' : " ({$times['start_time']}–{$times['end_time']})")),
                start: $start->setTimezone($timezone)->toIso8601String(),
                end: $end->setTimezone($timezone)->toIso8601String(),
                room: null,
                conflicts: $conflicts,
                person: $ref,
            );
        }

        return $items;
    }

    /**
     * Die Schreibarbeit **innerhalb** einer offenen Transaktion des Aufrufers.
     *
     * @param list<string> $days
     * @param array{start_time: string, end_time: string}|null $times
     * @return list<Vacation>
     */
    public function perform(
        User $actor,
        User|Freelancer|ServiceProvider $person,
        array $days,
        string $type,
        ?array $times,
        ?string $comment
    ): array {
        $vacationType = $type === self::TYPE_VACATION ? VacationType::OFF_WORK : VacationType::NOT_AVAILABLE;
        $vacations = [];

        foreach ($days as $day) {
            $request = new CreateVacationRequest([
                'date' => $day,
                'type' => 'vacation',
                'full_day' => $times === null,
                'start_time' => $times['start_time'] ?? null,
                'end_time' => $times['end_time'] ?? null,
                'is_series' => false,
                'comment' => $comment,
            ]);

            /** @var Vacation $vacation */
            $vacation = $this->vacationService->create(
                $person,
                $request,
                app(VacationConflictService::class),
                app(VacationSeriesService::class),
                app(ChangeService::class),
                app(SchedulingService::class),
                app(NotificationService::class),
                $vacationType
            );

            if ($vacation->getAttribute('created_by') === null) {
                $vacation->forceFill(['created_by' => $actor->id])->save();
            }

            $vacations[] = $vacation;
        }

        return $vacations;
    }

    /**
     * @param list<Vacation> $vacations
     * @return list<array{model_type: string, model_id: int, action: string, before_state: null}>
     */
    public function batchItems(array $vacations): array
    {
        return array_map(static fn (Vacation $vacation): array => [
            'model_type' => Vacation::class,
            'model_id' => (int) $vacation->id,
            'action' => 'create',
            'before_state' => null,
        ], $vacations);
    }

    /**
     * Dienstplan-Zeile der Person in anderen Clients nachladen (wie `VacationController`).
     */
    public function broadcast(User|Freelancer|ServiceProvider $person): void
    {
        SafeBroadcast::send(WorkerAvailabilityChanged::forMorph($person::class, (int) $person->getKey()));
    }

    /**
     * @param array{start_time: string, end_time: string}|null $times
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function window(string $day, ?array $times): array
    {
        $timezone = (string) config('app.timezone');
        $date = CarbonImmutable::parse($day, $timezone)->startOfDay();

        return $times === null
            ? [$date, $date->addDay()]
            : [$date->setTimeFromTimeString($times['start_time']), $date->setTimeFromTimeString($times['end_time'])];
    }

    /**
     * Zuweisungen der Person, deren (Pivot-)Zeitraum das Fenster beruehrt.
     */
    private function shiftsWithin(
        User|Freelancer|ServiceProvider $person,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): int {
        $pivots = ShiftWorker::query()
            ->where('employable_type', $person->getMorphClass())
            ->where('employable_id', $person->getKey())
            ->whereHas('shift', static fn ($query) => $query
                ->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('start_date', '>=', $start->subDay()->toDateString()))
            ->with('shift')
            ->get();

        $count = 0;
        foreach ($pivots as $pivot) {
            $shift = $pivot->shift;
            $interval = $shift instanceof Shift
                ? ShiftAssignmentPreflightService::resolvePivotInterval($pivot, $shift)
                : null;
            if (
                $interval !== null
                && ShiftAssignmentPreflightService::intervalsOverlap(
                    $interval[0],
                    $interval[1],
                    Carbon::instance($start),
                    Carbon::instance($end)
                )
            ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    private function summarize(array $items): AiWriteSummaryData
    {
        return new AiWriteSummaryData(
            self::TOOL_CREATE,
            count($items),
            count(array_filter($items, static fn (AiWriteItemData $item): bool => $item->conflicts !== [])),
            0
        );
    }
}

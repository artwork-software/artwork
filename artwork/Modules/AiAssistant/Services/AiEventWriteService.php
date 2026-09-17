<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Contracts\ActionGuard;
use Artwork\Modules\AiAssistant\Contracts\BatchRecorder;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteConflictData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteItemData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteLinkData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteSummaryData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\AiAssistant\Support\WebGuardScope;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventCollisionService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schreib-Endpunkte fuer artwork-ai (core-write-endpoints-dry-run; Konzept W1).
 *
 * **Keine eigene Schreiblogik.** Angelegt, geaendert und geloescht wird ausschliesslich ueber die
 * Bulk-Services der Oberflaeche (`EventService::createBulkEvent()`, `updateBulkEvent()`,
 * `bulkDeleteEvent()`). Weil keiner davon einen Vorschau-Modus hat, laeuft `dry_run=true` als
 * echte Transaktion, die anschliessend zurueckgerollt wird — die Vorschau zeigt damit das Ergebnis
 * der spaeteren Ausfuehrung und keine nachgebaute Schaetzung.
 *
 * Rechte werden mit denselben Policies geprueft wie in der Oberflaeche, aber im Guard `web`
 * (siehe `WebGuardScope`), sonst faellt unter `auth:api` jede Rechte- und Rollenpruefung still auf
 * `false`.
 */
class AiEventWriteService
{
    public const TOOL_CREATE = 'create_events';

    public const TOOL_UPDATE = 'update_event';

    public const TOOL_DELETE = 'delete_events';

    public function __construct(
        private readonly EventService $eventService,
        private readonly EventCollisionService $collisionService,
        private readonly ActionGuard $actionGuard,
        private readonly BatchRecorder $batchRecorder,
        private readonly AiBatchNotifier $notifier,
    ) {
    }

    /**
     * @param array{
     *     from: string, to: string, weekdays: list<int>, all_day: bool, start_time: string|null,
     *     duration_minutes: int|null, room_id: int, type_id: int, name: string|null,
     *     description: string|null
     * } $spec
     */
    public function createBatch(
        User $user,
        int $projectId,
        array $spec,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $project = Project::query()->visibleFor($user)->whereKey($projectId)->first();
        abort_if($project === null, Response::HTTP_NOT_FOUND);

        $room = Room::query()->whereKey($spec['room_id'])->first();
        abort_if($room === null, Response::HTTP_NOT_FOUND);

        $occurrences = $this->occurrences($spec);
        abort_if($occurrences === [], Response::HTTP_UNPROCESSABLE_ENTITY, 'Der Serienbeschrieb ergibt keinen Termin.');

        $limit = (int) config('ai.list_limit');
        abort_if(
            count($occurrences) > $limit,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            "Der Serienbeschrieb ergibt mehr als {$limit} Termine."
        );

        $conflicts = $this->creationPermissionConflicts($user, $project, $room);
        $this->assertExecutable($user, $intent, self::TOOL_CREATE, $conflicts);

        return $this->perform(
            $user,
            $intent,
            self::TOOL_CREATE,
            function () use ($occurrences, $project, $user, $spec): array {
                $events = [];

                foreach ($occurrences as $occurrence) {
                    $events[] = $this->eventService->createBulkEvent(
                        $this->creationPayload($occurrence, $spec),
                        $project,
                        $user->id
                    );
                }

                return $events;
            },
            fn (array $events): array => array_map(
                fn (Event $event): AiWriteItemData => $this->itemFor($event, 'create', $conflicts),
                $events
            ),
            static fn (array $events): array => array_map(
                static fn (Event $event): array => [
                    'model_type' => Event::class,
                    'model_id' => $event->id,
                    'action' => 'create',
                    'before_state' => null,
                ],
                $events
            ),
        );
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function update(
        User $user,
        int $eventId,
        array $changes,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        $event = Event::query()->visibleFor($user)->whereKey($eventId)->first();
        abort_if($event === null, Response::HTTP_NOT_FOUND);

        $conflicts = $this->policyConflicts($user, $event, 'update');
        $this->assertExecutable($user, $intent, self::TOOL_UPDATE, $conflicts);

        // getRawOriginal(), nicht getOriginal(): letzteres wendet die Casts an und liefert Carbon-
        // Objekte, die als JSON zu ISO-Strings werden — MySQL nimmt die beim Zurueckschreiben
        // nicht an. Roh gespeichert passt der Zustand wieder in die Spalten (core-undo).
        $beforeState = $event->getRawOriginal();

        return $this->perform(
            $user,
            $intent,
            self::TOOL_UPDATE,
            function () use ($event, $changes): array {
                $this->eventService->updateBulkEvent(collect($this->updatePayload($event, $changes)), $event);

                return [$event->refresh()];
            },
            fn (array $events): array => array_map(
                fn (Event $updated): AiWriteItemData => $this->itemFor(
                    $updated,
                    'update',
                    array_merge($conflicts, $this->collisionConflicts($updated))
                ),
                $events
            ),
            static fn (array $events): array => array_map(
                static fn (Event $updated): array => [
                    'model_type' => Event::class,
                    'model_id' => $updated->id,
                    'action' => 'update',
                    'before_state' => $beforeState,
                ],
                $events
            ),
        );
    }

    /**
     * @param list<int> $ids
     */
    public function deleteBatch(
        User $user,
        array $ids,
        AiWriteIntent $intent
    ): AiWritePreviewData|AiWriteResultData {
        // `project.managerUsers`, `creator` und `shifts.users` werden hier geladen, weil der
        // Notifier sie nach dem Loeschen braucht: die Zeilen sind dann weg, die geladenen
        // Beziehungen am Modell im Speicher aber noch da (core-notification-buendelung).
        $events = Event::query()
            ->visibleFor($user)
            ->whereIn('id', $ids)
            ->with(['room', 'project.managerUsers', 'creator', 'shifts.users'])
            ->get();
        abort_if($events->count() !== count($ids), Response::HTTP_NOT_FOUND);

        $conflicts = [];

        foreach ($events as $event) {
            $conflicts[$event->id] = $this->policyConflicts($user, $event, 'delete');
        }

        $this->assertExecutable(
            $user,
            $intent,
            self::TOOL_DELETE,
            array_merge([], ...array_values($conflicts))
        );

        $items = $events->map(
            fn (Event $event): AiWriteItemData => $this->itemFor($event, 'delete', $conflicts[$event->id])
        )->all();
        $batchItems = $events->map(static fn (Event $event): array => [
            'model_type' => Event::class,
            'model_id' => $event->id,
            'action' => 'delete',
            'before_state' => $event->getRawOriginal(),
        ])->all();

        return $this->perform(
            $user,
            $intent,
            self::TOOL_DELETE,
            function () use ($events): array {
                $this->eventService->bulkDeleteEvent(collect($events->modelKeys()));

                return $events->all();
            },
            static fn (): array => $items,
            static fn (): array => $batchItems,
        );
    }

    /**
     * Vorschau und Ausfuehrung laufen durch denselben Code: der Bulk-Service arbeitet in einer
     * Transaktion, die bei `dry_run` zurueckgerollt und sonst bestaetigt wird.
     *
     * @param callable(): list<Event> $work
     * @param callable(list<Event>): list<AiWriteItemData> $describe
     * @param callable(list<Event>): list<array{
     *     model_type: string, model_id: int, action: string, before_state: array<string, mixed>|null
     * }> $batchItems
     */
    private function perform(
        User $user,
        AiWriteIntent $intent,
        string $tool,
        callable $work,
        callable $describe,
        callable $batchItems
    ): AiWritePreviewData|AiWriteResultData {
        DB::beginTransaction();

        try {
            $events = $work();
            $items = $describe($events);
            $recorded = $batchItems($events);

            if ($intent->dryRun) {
                DB::rollBack();

                return new AiWritePreviewData(
                    dry_run: true,
                    tool: $tool,
                    items: $items,
                    summary: $this->summarize($tool, $items),
                );
            }

            $batch = $this->batchRecorder->record($user, $tool, $recorded);

            // Aktion abschliessen und Write-Token revozieren — in derselben Transaktion wie die
            // Ausfuehrung (W2 §3), sonst gaebe es ausgefuehrte Aenderungen mit offener Aktion.
            $this->actionGuard->complete($user, (string) $intent->actionId, $batch?->id);

            DB::commit();

            // Erst nach dem Commit: eine Sammelmeldung je betroffenem Nutzer und ein
            // Refresh-Broadcast fuer den ganzen Batch (W4).
            $this->notifier->notify($user, $this->notifiedAction($tool), $events);

            return new AiWriteResultData(
                dry_run: false,
                tool: $tool,
                batch_id: $batch?->id,
                undo_until: $batch?->undoUntil,
                ids: array_map(static fn (Event $event): int => $event->id, $events),
                items: $items,
                links: $this->linksFor($tool, $events),
                summary: $this->summarize($tool, $items),
            );
        } catch (\Throwable $throwable) {
            DB::rollBack();

            throw $throwable;
        }
    }

    private function notifiedAction(string $tool): string
    {
        return match ($tool) {
            self::TOOL_CREATE => AiBatchNotifier::ACTION_CREATED,
            self::TOOL_DELETE => AiBatchNotifier::ACTION_DELETED,
            default => AiBatchNotifier::ACTION_UPDATED,
        };
    }

    /**
     * @param array{
     *     from: string, to: string, weekdays: list<int>, all_day: bool, start_time: string|null,
     *     duration_minutes: int|null, room_id: int, type_id: int, name: string|null,
     *     description: string|null
     * } $spec
     * @return list<CarbonImmutable>
     */
    private function occurrences(array $spec): array
    {
        $day = CarbonImmutable::parse($spec['from'])->startOfDay();
        $last = CarbonImmutable::parse($spec['to'])->startOfDay();
        $weekdays = $spec['weekdays'];
        $occurrences = [];

        while (!$day->greaterThan($last)) {
            if ($weekdays === [] || in_array($day->isoWeekday(), $weekdays, true)) {
                $occurrences[] = $day;
            }

            $day = $day->addDay();
        }

        return $occurrences;
    }

    /**
     * Ganztaegige Termine gehen **ohne** Zeiten an den Bulk-Service: `processEventTimes()`
     * (`EventService.php:2053-2073`) setzt dann `allDay` und dehnt den Termin auf den ganzen Tag.
     * Selbst zu rechnen hiesse, dieselbe Regel ein zweites Mal zu pflegen.
     *
     * @param array{
     *     all_day: bool, start_time: string|null, duration_minutes: int|null, room_id: int,
     *     type_id: int, name: string|null, description: string|null, from: string, to: string,
     *     weekdays: list<int>
     * } $spec
     * @return array<string, mixed>
     */
    private function creationPayload(CarbonImmutable $day, array $spec): array
    {
        $start = $spec['all_day'] || $spec['start_time'] === null
            ? null
            : $day->setTimeFromTimeString($spec['start_time']);
        $end = $start === null ? null : $start->addMinutes((int) $spec['duration_minutes']);

        return [
            'day' => $day->toDateString(),
            'start_time' => $start?->format('H:i'),
            'end_time' => $end?->format('H:i'),
            'name' => $spec['name'] ?? '',
            'description' => $spec['description'],
            'type' => ['id' => $spec['type_id']],
            'room' => ['id' => $spec['room_id']],
            'is_planning' => false,
        ];
    }

    /**
     * Der Bulk-Service der Oberflaeche erwartet den vollstaendigen Satz; was der Aufruf nicht
     * mitschickt, kommt aus dem Termin.
     *
     * Ganztaegig ist auch hier die Abwesenheit von Zeiten (siehe `creationPayload()`). Ohne
     * `all_day` im Aufruf behaelt der Termin seine Eigenschaft: fuer einen ganztaegigen Termin
     * stuenden sonst dessen Randzeiten (00:00 und 23:59) im Satz, und der Bulk-Service machte
     * daraus einen Termin mit Uhrzeit (core-api-lookups).
     *
     * @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private function updatePayload(Event $event, array $changes): array
    {
        return [
            'name' => $changes['name'] ?? ($event->eventName ?: $event->name),
            'day' => $changes['day'] ?? CarbonImmutable::parse($event->start_time)->toDateString(),
            ...$this->updateTimes($event, $changes),
            'type' => ['id' => $changes['type_id'] ?? $event->event_type_id],
            'room' => ['id' => $changes['room_id'] ?? $event->room_id],
            'is_planning' => (bool) $event->is_planning,
        ];
    }

    /**
     * @param array<string, mixed> $changes
     * @return array{start_time: string|null, end_time: string|null}
     */
    private function updateTimes(Event $event, array $changes): array
    {
        $allDay = array_key_exists('all_day', $changes) ? (bool) $changes['all_day'] : (bool) $event->allDay;

        if ($allDay) {
            return ['start_time' => null, 'end_time' => null];
        }

        return [
            'start_time' => $changes['start_time'] ?? CarbonImmutable::parse($event->start_time)->format('H:i'),
            'end_time' => $changes['end_time'] ?? CarbonImmutable::parse($event->end_time)->format('H:i'),
        ];
    }

    /**
     * Rechte fuer die Anlage: dieselbe Kette wie `EventController::bulkProjectEventStore()` —
     * Termin anlegen duerfen, das Projekt sehen duerfen und den Raum belegen duerfen.
     *
     * @return list<AiWriteConflictData>
     */
    private function creationPermissionConflicts(User $user, Project $project, Room $room): array
    {
        $allowed = WebGuardScope::run(static function () use ($user, $project, $room): bool {
            $gate = Gate::forUser($user);

            if (!$gate->allows('create', Event::class) || !$gate->allows('view', $project)) {
                return false;
            }

            return $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value)
                || (bool) $room->everyone_can_book
                || $room->admins()->where('user_id', $user->id)->exists();
        });

        return $allowed ? [] : [new AiWriteConflictData(
            AiWriteConflictData::MISSING_PERMISSION,
            'Diese Person darf in diesem Raum keine Termine anlegen.',
            null
        )];
    }

    /**
     * @return list<AiWriteConflictData>
     */
    private function policyConflicts(User $user, Event $event, string $ability): array
    {
        $allowed = WebGuardScope::run(
            static fn (): bool => Gate::forUser($user)->allows($ability, $event)
        );

        return $allowed ? [] : [new AiWriteConflictData(
            AiWriteConflictData::MISSING_PERMISSION,
            $ability === 'delete'
                ? 'Diese Person darf diesen Termin nicht loeschen.'
                : 'Diese Person darf diesen Termin nicht aendern.',
            AiRefData::make($event->id, AiText::truncate($event->eventName ?: $event->name))
        )];
    }

    /**
     * Raumkollisionen ueber denselben Dienst wie die Oberflaeche; der Termin selbst ist
     * ausgenommen.
     *
     * @return list<AiWriteConflictData>
     */
    private function collisionConflicts(Event $event): array
    {
        if ($event->room_id === null || $event->start_time === null || $event->end_time === null) {
            return [];
        }

        $request = new Request([
            'start' => $event->start_time->toDateTimeString(),
            'end' => $event->end_time->toDateTimeString(),
            'roomId' => $event->room_id,
        ]);

        $conflicts = [];

        foreach ($this->collisionService->getCollision($request, $event)->get() as $collision) {
            $conflicts[] = new AiWriteConflictData(
                AiWriteConflictData::ROOM_COLLISION,
                'Der Raum ist in dieser Zeit bereits belegt.',
                AiRefData::make($collision->id, AiText::truncate($collision->eventName ?: $collision->name))
            );
        }

        return $conflicts;
    }

    /**
     * @param list<AiWriteConflictData> $permissionConflicts
     */
    private function assertExecutable(
        User $user,
        AiWriteIntent $intent,
        string $tool,
        array $permissionConflicts
    ): void {
        if ($intent->dryRun) {
            return;
        }

        // Fehlende Rechte sind in der Ausfuehrung ein 403 und kein Teilergebnis — genau wie in
        // `bulkProjectEventStore()`, das erst alle Zeilen autorisiert und dann schreibt.
        abort_unless($permissionConflicts === [], Response::HTTP_FORBIDDEN);

        $this->actionGuard->assertExecutable($user, (string) $intent->actionId, $tool, $intent->args);
    }

    /**
     * @param list<AiWriteConflictData> $conflicts
     */
    private function itemFor(Event $event, string $action, array $conflicts): AiWriteItemData
    {
        $timezone = (string) config('app.timezone');
        $collisions = $action === 'create' ? $this->collisionConflicts($event) : [];

        return new AiWriteItemData(
            action: $action,
            id: $event->id,
            label: AiText::truncate($event->eventName ?: $event->name),
            start: $event->start_time?->copy()->setTimezone($timezone)->toIso8601String(),
            end: $event->end_time?->copy()->setTimezone($timezone)->toIso8601String(),
            room: AiRefData::make($event->room_id, $event->room?->name),
            conflicts: array_merge($conflicts, $collisions),
        );
    }

    /**
     * Links auf die betroffenen Objekte (W3 §3). Nach einer Loeschung gibt es nichts zu oeffnen.
     *
     * @param list<Event> $events
     * @return list<AiWriteLinkData>
     */
    private function linksFor(string $tool, array $events): array
    {
        if ($tool === self::TOOL_DELETE) {
            return [];
        }

        return array_map(
            static fn (Event $event): AiWriteLinkData => new AiWriteLinkData(
                id: $event->id,
                url: route('dashboard.redirect-to-calendar', $event->id),
            ),
            $events
        );
    }

    /**
     * @param list<AiWriteItemData> $items
     */
    private function summarize(string $tool, array $items): AiWriteSummaryData
    {
        $withConflicts = 0;
        $blocked = 0;

        foreach ($items as $item) {
            if ($item->conflicts !== []) {
                $withConflicts++;
            }

            foreach ($item->conflicts as $conflict) {
                if ($conflict->type === AiWriteConflictData::MISSING_PERMISSION) {
                    $blocked++;

                    break;
                }
            }
        }

        return new AiWriteSummaryData(
            action: $tool,
            total: count($items),
            with_conflicts: $withConflicts,
            blocked: $blocked,
        );
    }
}

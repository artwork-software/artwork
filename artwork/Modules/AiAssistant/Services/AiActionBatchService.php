<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Listeners\AttachActivityOrigin;
use Artwork\Modules\AiAssistant\Models\AiActionBatch;
use Artwork\Modules\AiAssistant\Models\AiActionBatchItem;
use Artwork\Modules\AiAssistant\Support\RecordedBatch;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftWorkerService;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Vacation\Services\VacationService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aufzeichnung und Ruecknahme ausgefuehrter KI-Aktionen (core-undo; Konzept W3).
 *
 * Ein Batch entsteht **in** der Transaktion der Ausfuehrung; der Revert laeuft ebenfalls in einer
 * Transaktion und kennt kein Teilergebnis (W3 §2).
 */
class AiActionBatchService
{
    /**
     * Modelle, die ueberhaupt zurueckgenommen werden koennen. Was in der Datenbank steht, wird
     * bewusst nicht als Klassenname instanziiert.
     *
     * @var list<class-string<Model>>
     */
    private const REVERTABLE = [
        Event::class,
        ShiftWorker::class,
        Shift::class,
        Task::class,
        Checklist::class,
        Vacation::class,
    ];

    /** @var array<int, array{user: User, from: string, to: string}> Personen, deren Regeln nach dem Revert neu zu pruefen sind. */
    private array $revalidate = [];

    public function __construct(
        private readonly AiBatchNotifier $notifier,
        private readonly ShiftWorkerService $shiftWorkerService,
        private readonly ShiftService $shiftService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftRuleService $shiftRuleService,
        private readonly VacationService $vacationService,
    ) {
    }

    /**
     * @param list<array{
     *     model_type: string,
     *     model_id: int,
     *     action: string,
     *     before_state: array<string, mixed>|null
     * }> $items
     */
    public function record(User $user, string $tool, array $items): ?RecordedBatch
    {
        if ($items === []) {
            return null;
        }

        $executedAt = CarbonImmutable::now();
        $undoUntil = $executedAt->addHours((int) config('ai.undo_hours'));

        $batch = AiActionBatch::query()->create([
            'user_id' => $user->id,
            'conversation_id' => $this->conversationId(),
            'tool' => $tool,
            'status' => AiActionBatch::STATUS_EXECUTED,
            'executed_at' => $executedAt,
            'undo_until' => $undoUntil,
        ]);

        foreach ($items as $item) {
            $batch->items()->create([
                'model_type' => $item['model_type'],
                'model_id' => $item['model_id'],
                'action' => $item['action'],
                'before_state' => $item['before_state'],
            ]);
        }

        return new RecordedBatch($batch->id, $undoUntil->toIso8601String());
    }

    /**
     * Ruecknahme (W3 §2). Eigentuemerschaft direkt geprueft — ein Admin darf einen fremden Batch
     * nicht zuruecknehmen (HANDOVER_core §6).
     */
    public function revert(User $user, string $batchId): AiActionBatch
    {
        $batch = AiActionBatch::query()->with('items')->find($batchId);

        abort_if($batch === null, Response::HTTP_NOT_FOUND);
        abort_unless($batch->user_id === $user->id, Response::HTTP_FORBIDDEN);

        abort_unless(
            $batch->status === AiActionBatch::STATUS_EXECUTED,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Diese Aktion wurde bereits zurueckgenommen.'
        );

        abort_if(
            $batch->undo_until->isPast(),
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'Die Frist zum Rueckgaengigmachen ist abgelaufen.'
        );

        $conflicts = $this->conflicts($batch);

        abort_if(
            $conflicts !== [],
            Response::HTTP_CONFLICT,
            'Seit der Ausfuehrung wurden Objekte veraendert: ' . implode(', ', $conflicts)
        );

        /** @var list<Event> $reverted */
        $reverted = [];
        $this->revalidate = [];

        // Der Revert gehoert zum Assistenten, obwohl die Person ihn in der Oberflaeche ausloest
        // (W3 §2). Ohne den Override schriebe der Herkunfts-Listener `ui`.
        $result = AttachActivityOrigin::during(
            AttachActivityOrigin::VIA_AI_ASSISTANT,
            $batch->conversation_id,
            // Bewusst keine Pfeilfunktion: die wuerde `$reverted` by value einfangen, und die
            // Referenz der inneren Closure zeigte dann auf die Kopie.
            function () use ($batch, &$reverted): AiActionBatch {
                return DB::transaction(function () use ($batch, &$reverted): AiActionBatch {
                    foreach ($batch->items as $item) {
                        $model = $this->revertItem($item);

                        if ($model instanceof Event) {
                            $reverted[] = $model;
                        }
                    }

                    // Nach entfernten Zuweisungen Regeln neu pruefen wie `ShiftController::removeFromShift()`.
                    foreach ($this->revalidate as $entry) {
                        $this->shiftRuleService->validateRulesForUser(
                            $entry['user'],
                            Carbon::parse($entry['from'])->subDay(),
                            Carbon::parse($entry['to'])->addDay()
                        );
                    }

                    $batch->forceFill([
                        'status' => AiActionBatch::STATUS_REVERTED,
                        'reverted_at' => CarbonImmutable::now(),
                    ])->save();

                    return $batch;
                });
            }
        );

        // Sammelmeldung und Kalender-Refresh nach der Ruecknahme (W4). Erst nach der Transaktion:
        // vorher stuende in der Meldung ein Zustand, der noch zurueckgerollt werden koennte.
        $this->notifier->notify($user, AiBatchNotifier::ACTION_REVERTED, $reverted);

        return $result;
    }

    /**
     * Objekte, die seit der Ausfuehrung von jemand anderem angefasst wurden (W3 §4).
     *
     * Verglichen wird strikt groesser: die Aenderung der Ausfuehrung selbst faellt in dieselbe
     * Sekunde wie `executed_at` und darf nicht als Konflikt zaehlen.
     *
     * @return list<string>
     */
    private function conflicts(AiActionBatch $batch): array
    {
        $conflicts = [];

        foreach ($batch->items as $item) {
            $model = $this->resolve($item);

            if ($model === null) {
                continue;
            }

            $updatedAt = $model->getAttribute('updated_at');

            if ($updatedAt !== null && $updatedAt->greaterThan($batch->executed_at)) {
                $conflicts[] = class_basename($item->model_type) . ' #' . $item->model_id;
            } elseif ($model instanceof Shift && $this->hasForeignAssignments($batch, $model, $item)) {
                $conflicts[] = 'Shift #' . $item->model_id . ' (inzwischen mit Zuweisungen)';
            }
        }

        return $conflicts;
    }

    /**
     * Eine angelegte Schicht wird nur zurueckgenommen, solange niemand ausser den Zuweisungen
     * desselben Batches in ihr steht (ai-scenarios-4 S13) — sonst 409.
     */
    private function hasForeignAssignments(AiActionBatch $batch, Shift $shift, AiActionBatchItem $item): bool
    {
        if ($item->action !== AiActionBatchItem::ACTION_CREATE || $shift->trashed()) {
            return false;
        }

        $own = $batch->items
            ->filter(static fn (AiActionBatchItem $other): bool => $other->model_type === ShiftWorker::class
                && $other->action === AiActionBatchItem::ACTION_CREATE)
            ->pluck('model_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        return ShiftWorker::query()
            ->where('shift_id', $shift->id)
            ->whereNotIn('id', $own)
            ->exists();
    }

    /**
     * Gibt das betroffene Modell zurueck, damit der Aufrufer danach benachrichtigen kann (W4).
     */
    private function revertItem(AiActionBatchItem $item): ?Model
    {
        if ($item->model_type === ShiftWorker::class && $item->action === AiActionBatchItem::ACTION_DELETE) {
            $this->reassignFromState($item);

            return null;
        }

        $model = $this->resolve($item);

        if ($model === null) {
            // Objekt ist nicht mehr da (hart geloescht) — dann gibt es auch nichts zurueckzunehmen.
            return null;
        }

        if ($model instanceof ShiftWorker || $model instanceof Shift) {
            $this->revertShiftItem($model, $item);

            return $model;
        }

        if ($model instanceof Vacation) {
            // Wie „Abwesenheit loeschen" in der Oberflaeche (Konflikte und Stunden-Cache mit).
            if ($item->action === AiActionBatchItem::ACTION_CREATE) {
                $this->vacationService->delete($model);
            }

            return null;
        }

        match ($item->action) {
            AiActionBatchItem::ACTION_CREATE => $model->delete(),
            AiActionBatchItem::ACTION_UPDATE => $this->restoreState($model, $item),
            AiActionBatchItem::ACTION_DELETE => $this->undelete($model),
            default => null,
        };

        return $model;
    }

    /**
     * Schichtplanung (ai-shift-planning): eine angelegte Zuweisung geht ueber denselben Weg wie
     * „Aus Schicht entfernen" in der Oberflaeche (`ShiftWorkerService::removeFromShift()`, inkl.
     * Benachrichtigung bei festgeschriebenen Schichten), eine angelegte Schicht ueber
     * `ShiftService::delete()`. Eine entfernte Zuweisung (`unassign_shifts`) wird wieder zugewiesen.
     */
    private function revertShiftItem(ShiftWorker|Shift $model, AiActionBatchItem $item): void
    {
        if ($model instanceof Shift) {
            if ($item->action === AiActionBatchItem::ACTION_CREATE && !$model->trashed()) {
                $this->shiftService->delete($model, $this->shiftsQualificationsService);
            } elseif ($item->action === AiActionBatchItem::ACTION_UPDATE) {
                // Vom Termin mitverschobene Schichten (ai-scenarios-4 S12) zurueck auf ihr Datum.
                $this->restoreState($model, $item);
            }

            return;
        }

        $shift = Shift::query()->find($model->shift_id);
        $worker = $model->employable;

        if ($item->action === AiActionBatchItem::ACTION_CREATE) {
            if (!$model->trashed()) {
                $this->shiftWorkerService->removeFromShift(
                    $model,
                    true,
                    app(NotificationService::class),
                    app(VacationConflictService::class),
                    app(AvailabilityConflictService::class),
                    app(ChangeService::class),
                );
            }
        }

        $this->rememberForRevalidation($worker, $shift);
    }

    /**
     * Ruecknahme von `unassign_shifts`: `removeFromShift()` loescht die Zuweisung hart, deshalb steht
     * sie im `before_state` und wird von dort ueber `assignToShift()` wieder angelegt.
     */
    private function reassignFromState(AiActionBatchItem $item): void
    {
        $state = $item->before_state ?? [];
        $shift = Shift::query()->find((int) ($state['shift_id'] ?? 0));
        $class = (string) ($state['employable_type'] ?? '');
        $worker = in_array($class, [User::class, Freelancer::class, ServiceProvider::class], true)
            ? $class::query()->find((int) ($state['employable_id'] ?? 0))
            : null;

        if ($shift === null || $worker === null) {
            return;
        }

        $this->shiftWorkerService->assignToShift(
            $shift,
            $worker,
            (int) $state['shift_qualification_id'],
            (string) ($state['craft_abbreviation'] ?? $shift->craft?->abbreviation ?? ''),
            app(NotificationService::class),
            app(VacationConflictService::class),
            app(AvailabilityConflictService::class),
            app(ChangeService::class),
            null,
            (bool) ($state['is_overbooked'] ?? false),
        );

        $this->rememberForRevalidation($worker, $shift);
    }

    private function rememberForRevalidation(?Model $worker, ?Shift $shift): void
    {
        if ($worker instanceof User && $shift !== null) {
            $this->revalidate[$worker->id] = [
                'user' => $worker,
                'from' => min(
                    $this->revalidate[$worker->id]['from'] ?? '9999-12-31',
                    Carbon::parse($shift->start_date)->toDateString()
                ),
                'to' => max(
                    $this->revalidate[$worker->id]['to'] ?? '0000-01-01',
                    Carbon::parse($shift->end_date ?? $shift->start_date)->toDateString()
                ),
            ];
        }
    }

    private function restoreState(Model $model, AiActionBatchItem $item): void
    {
        $before = $item->before_state ?? [];

        if ($before === []) {
            return;
        }

        // `updated_at` steht im Zustand von vorher und ist damit dirty — Eloquent laesst es dann
        // stehen, statt es auf jetzt zu setzen. Der Datensatz ist danach exakt wie vorher.
        $model->forceFill($before)->save();
    }

    private function undelete(Model $model): void
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            /** @var Model&\Illuminate\Database\Eloquent\SoftDeletes $model */
            $model->restore();
        }
    }

    private function resolve(AiActionBatchItem $item): ?Model
    {
        if (!in_array($item->model_type, self::REVERTABLE, true)) {
            return null;
        }

        /** @var class-string<Model> $class */
        $class = $item->model_type;
        $query = $class::query();

        if ($class === Event::class) {
            // Fuer die Sammelmeldung: beim Zuruecknehmen eines Anlege-Batches wird der Termin
            // geloescht — die Empfaenger muessen vorher am Modell haengen (W4).
            $query->with(['project.managerUsers', 'creator', 'shifts.users']);
        }

        if (in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->find($item->model_id);
    }

    private function conversationId(): ?string
    {
        if (!app()->bound('request')) {
            return null;
        }

        $request = app('request');

        if (!$request instanceof Request) {
            return null;
        }

        $value = $request->header(AttachActivityOrigin::CONVERSATION_HEADER);

        return is_string($value) && Str::isUuid($value) ? $value : null;
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\Listeners\AttachActivityOrigin;
use Artwork\Modules\AiAssistant\Models\AiActionBatch;
use Artwork\Modules\AiAssistant\Models\AiActionBatchItem;
use Artwork\Modules\AiAssistant\Support\RecordedBatch;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
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
    private const REVERTABLE = [Event::class];

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

        // Der Revert gehoert zum Assistenten, obwohl die Person ihn in der Oberflaeche ausloest
        // (W3 §2). Ohne den Override schriebe der Herkunfts-Listener `ui`.
        return AttachActivityOrigin::during(
            AttachActivityOrigin::VIA_AI_ASSISTANT,
            $batch->conversation_id,
            fn (): AiActionBatch => DB::transaction(function () use ($batch): AiActionBatch {
                foreach ($batch->items as $item) {
                    $this->revertItem($item);
                }

                $batch->forceFill([
                    'status' => AiActionBatch::STATUS_REVERTED,
                    'reverted_at' => CarbonImmutable::now(),
                ])->save();

                return $batch;
            })
        );
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
            }
        }

        return $conflicts;
    }

    private function revertItem(AiActionBatchItem $item): void
    {
        $model = $this->resolve($item);

        if ($model === null) {
            // Objekt ist nicht mehr da (hart geloescht) — dann gibt es auch nichts zurueckzunehmen.
            return;
        }

        match ($item->action) {
            AiActionBatchItem::ACTION_CREATE => $model->delete(),
            AiActionBatchItem::ACTION_UPDATE => $this->restoreState($model, $item),
            AiActionBatchItem::ACTION_DELETE => $this->undelete($model),
            default => null,
        };
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

<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiEventTypeData;
use Artwork\Modules\AiAssistant\DTOs\AiEventTypeListData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomListData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Raeume und Terminarten nachschlagen (core-api-lookups; Konzept B1 §3, W1 §1).
 *
 * Zweck ist das Aufloesen von Namen in IDs: `create_events` und `update_event` verlangen
 * `room_id` und `type_id`, und bis hierher hatte das Modell keine Liste, aus der es sie nehmen
 * konnte (Fund aus `ai-write-tools-confirm`).
 *
 * Gesucht wird mit `LIKE` statt mit dem im Brief genannten `ILIKE`: der Core laeuft auf MySQL, wo
 * es `ILIKE` nicht gibt und der Vergleich ueber die Collation ohnehin unabhaengig von
 * Gross-/Kleinschreibung ist.
 */
class AiLookupReadService
{
    public function rooms(User $user, ?string $search, ?int $limit): AiRoomListData
    {
        $limit = $this->limit($limit);

        $query = Room::query()
            ->visibleFor($user)
            ->when(
                $search !== null,
                fn (Builder $builder) => $builder->where('name', 'like', $this->contains((string) $search))
            );

        $total = (clone $query)->count();

        $rooms = $query
            ->with('area')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return new AiRoomListData(
            data: $rooms->map(static fn (Room $room): AiRoomData => new AiRoomData(
                id: $room->id,
                name: AiText::truncate($room->name),
                area: AiRefData::make($room->area_id, $room->area?->name),
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * Terminarten mit genau einer Default-Markierung.
     *
     * Der Default ist die Zeile mit `fallback_type` — dieselbe Kennzeichnung, die die Oberflaeche
     * benutzt; gibt es keine, ist es die alphabetisch erste. Bestimmt wird er ueber den
     * **gesamten** Bestand, nicht ueber die ausgelieferte Seite, und dieselbe Sortierung stellt
     * ihn an die erste Stelle. Schraenkt `q` die Liste ein, kann der Default herausfallen — dann
     * traegt die Antwort keinen, was richtig ist: sie ist dann keine vollstaendige Auswahl.
     */
    public function eventTypes(?string $search, ?int $limit): AiEventTypeListData
    {
        $limit = $this->limit($limit);

        $query = EventType::query()
            ->when(
                $search !== null,
                fn (Builder $builder) => $builder->where('name', 'like', $this->contains((string) $search))
            );

        $total = (clone $query)->count();

        $defaultId = $this->sorted(EventType::query())->value('id');

        $types = $this->sorted($query)
            ->limit($limit)
            ->get();

        return new AiEventTypeListData(
            data: $types->map(static fn (EventType $type): AiEventTypeData => new AiEventTypeData(
                id: $type->id,
                name: AiText::truncate($type->name),
                abbreviation: AiText::truncate($type->abbreviation),
                is_default: $type->id === $defaultId,
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * @param Builder<EventType> $query
     * @return Builder<EventType>
     */
    private function sorted(Builder $query): Builder
    {
        return $query->orderByDesc('fallback_type')->orderBy('name');
    }

    /**
     * Suchmuster fuer `LIKE`: Platzhalter im Suchwort bleiben Text.
     */
    private function contains(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }
}

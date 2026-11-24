<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiEventStatsGroupData;
use Artwork\Modules\AiAssistant\DTOs\AiEventStatsListData;
use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Terminanzahl je Projekt, Raum oder Terminart (core-ai-event-stats-exports).
 *
 * Grundlage sind dieselben Termine wie bei GET /ai/events: `Event::visibleFor($user)`, Ueberlappung
 * mit dem Zeitraum. Ein mehrtaegiger Termin zaehlt einmal. Beschriftungen kommen wie in
 * AiEventData direkt aus dem Termin — wer den Termin sieht, sieht auch, wozu er gehoert.
 *
 * Feedback 17.11.: „Welche Projekte hatten mehr als 30 Termine im ersten Quartal?" liess sich vorher
 * nur ueber einen Aufruf je Projekt beantworten; `event_count` in /ai/projects zaehlt die gesamte
 * Laufzeit.
 */
class AiEventStatsService
{
    private const COLUMNS = ['project' => 'project_id', 'room' => 'room_id', 'type' => 'event_type_id'];

    /**
     * @param array{room_ids?: list<int>, project_id?: int|null, type_id?: int|null, min_count?: int|null,
     *     max_count?: int|null, limit?: int|null} $filters
     */
    public function stats(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        string $groupBy,
        array $filters
    ): AiEventStatsListData {
        $column = self::COLUMNS[$groupBy];
        $limit = $this->limit($filters['limit'] ?? null);

        /** @var array<int|string, int> $counts group id ('' fuer null) => Anzahl */
        $counts = Event::query()
            ->visibleFor($user)
            ->where('start_time', '<=', $to)
            ->where('end_time', '>=', $from)
            ->when(
                ($filters['room_ids'] ?? []) !== [],
                fn (Builder $builder) => $builder->whereIn('room_id', $filters['room_ids'])
            )
            ->when(
                ($filters['project_id'] ?? null) !== null,
                fn (Builder $builder) => $builder->where('project_id', $filters['project_id'])
            )
            ->when(
                ($filters['type_id'] ?? null) !== null,
                fn (Builder $builder) => $builder->where('event_type_id', $filters['type_id'])
            )
            ->groupBy($column)
            ->selectRaw("{$column} as group_id, count(*) as event_count")
            ->pluck('event_count', 'group_id')
            ->map(fn ($count): int => (int) $count)
            ->all();

        $totalEvents = array_sum($counts);
        $min = $filters['min_count'] ?? null;
        $max = $filters['max_count'] ?? null;
        $counts = array_filter(
            $counts,
            fn (int $count): bool => ($min === null || $count >= $min) && ($max === null || $count <= $max)
        );

        $names = $this->names($groupBy, array_values(array_filter(
            array_map(fn ($id): int => (int) $id, array_keys($counts)),
            fn (int $id): bool => $id > 0
        )));

        $groups = [];
        foreach ($counts as $id => $count) {
            $id = $id === '' || $id === null ? null : (int) $id;
            $groups[] = new AiEventStatsGroupData(
                id: $id,
                name: $id === null ? null : AiText::truncate($names[$id] ?? null),
                count: $count,
            );
        }

        // Absteigend nach Anzahl, bei Gleichstand nach Name — stabil fuer Tests und Tabellen
        usort(
            $groups,
            fn (AiEventStatsGroupData $a, AiEventStatsGroupData $b): int => [$b->count, (string) $a->name]
                <=> [$a->count, (string) $b->name]
        );

        return new AiEventStatsListData(
            data: array_slice($groups, 0, $limit),
            group_by: $groupBy,
            period: AiPeriodData::fromDates($from, $to),
            total_events: $totalEvents,
            total_groups: count($groups),
            limit: $limit,
            truncated: count($groups) > $limit,
        );
    }

    /**
     * @param list<int> $ids
     * @return array<int, string|null>
     */
    private function names(string $groupBy, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $model = match ($groupBy) {
            'project' => Project::class,
            'room' => Room::class,
            default => EventType::class,
        };

        /** @var array<int, string|null> */
        return $model::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.stats_limit', 200);

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiConflictEventData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomConflictData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomConflictListData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Doppelbelegungen von Raeumen (ai-scenarios-3 S6): Paare ueberlappender Termine im selben Raum.
 *
 * Gelesen werden nicht geloeschte Termine (SoftDeletes greift ueber das Modell) ohne
 * Planungstermine (`is_planning`) — die stehen im eigenen Planungskalender und belegen den Raum
 * noch nicht. Ueberlappung strikt (Ende = Beginn ist keine). Termine aus Projekten, die die
 * fragende Person nicht sehen darf, erscheinen anonym — dieselbe Datenschutzlinie wie bei der
 * Raumbelegung (B1 §5).
 */
class AiRoomConflictService
{
    public const ANONYMOUS = 'belegt';

    /**
     * @return array{0: list<AiRoomConflictData>, 1: int}
     */
    public function find(User $user, CarbonImmutable $from, CarbonImmutable $to, ?int $roomId, int $limit): array
    {
        $events = Event::query()
            ->whereNotNull('room_id')
            ->where('is_planning', false)
            ->where('start_time', '<', $to->endOfDay())
            ->where('end_time', '>', $from->startOfDay())
            ->when($roomId !== null, static fn ($query) => $query->where('room_id', $roomId))
            ->with(['room', 'project'])
            ->orderBy('room_id')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        $visibleProjects = Project::query()
            ->visibleFor($user)
            ->whereIn('id', $events->pluck('project_id')->filter()->unique()->values())
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $conflicts = [];

        foreach ($events->groupBy('room_id') as $roomEvents) {
            $conflicts = array_merge($conflicts, $this->pairs($roomEvents->values(), $visibleProjects));
        }

        usort($conflicts, static fn (AiRoomConflictData $a, AiRoomConflictData $b): int => [$a->start, $a->room->id]
            <=> [$b->start, $b->room->id]);

        return [array_slice($conflicts, 0, $limit), count($conflicts)];
    }

    public function list(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $roomId,
        ?int $limit
    ): AiRoomConflictListData {
        $max = (int) config('ai.list_limit');
        $limit = $limit === null || $limit < 1 ? $max : min($limit, $max);
        [$conflicts, $total] = $this->find($user, $from, $to, $roomId, $limit);

        return new AiRoomConflictListData($conflicts, $total, $limit, $total > $limit);
    }

    /**
     * @param Collection<int, Event> $events nach Beginn sortiert
     * @param list<int> $visibleProjects
     * @return list<AiRoomConflictData>
     */
    private function pairs(Collection $events, array $visibleProjects): array
    {
        $pairs = [];
        $count = $events->count();

        for ($i = 0; $i < $count; $i++) {
            $first = $events[$i];

            for ($j = $i + 1; $j < $count; $j++) {
                $second = $events[$j];

                if (!$second->start_time->lessThan($first->end_time)) {
                    break;
                }

                $start = $first->start_time->max($second->start_time);
                $end = $first->end_time->min($second->end_time);

                $pairs[] = new AiRoomConflictData(
                    room: new AiRefData((int) $first->room_id, AiText::truncate($first->room?->name)),
                    start: $this->iso($start),
                    end: $this->iso($end),
                    events: [
                        $this->eventData($first, $visibleProjects),
                        $this->eventData($second, $visibleProjects),
                    ],
                );
            }
        }

        return $pairs;
    }

    /**
     * @param list<int> $visibleProjects
     */
    private function eventData(Event $event, array $visibleProjects): AiConflictEventData
    {
        $hidden = $event->project_id !== null && !in_array((int) $event->project_id, $visibleProjects, true);

        return new AiConflictEventData(
            id: $hidden ? null : (int) $event->id,
            name: $hidden ? self::ANONYMOUS : (string) AiText::truncate($event->eventName ?: $event->name),
            start: $this->iso($event->start_time),
            end: $this->iso($event->end_time),
            project: $hidden ? null : AiRefData::make($event->project_id, $event->project?->name),
        );
    }

    private function iso(?\DateTimeInterface $moment): string
    {
        return CarbonImmutable::instance($moment ?? now())
            ->setTimezone((string) config('app.timezone'))
            ->toIso8601String();
    }
}

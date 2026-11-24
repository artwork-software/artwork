<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiAvailableRoomData;
use Artwork\Modules\AiAssistant\DTOs\AiAvailableRoomListData;
use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\WebGuardScope;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomCategory;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Alle freien Raeume zu einem Zeitfenster (core-ai-aggregates, Entgrenzen P1).
 *
 * Wie Belegung und freie Slots (`AiEventReadService`) zaehlen **alle** Termine eines Raums, auch
 * solche, die die Person nicht sehen darf: ein belegter Raum ist ein Faktum. Die Antwort enthaelt
 * nur Raeume, nie Termine, Namen oder Projekte.
 */
class AiRoomAvailabilityService
{
    /**
     * @param array{room_category_id?: int|null, area_id?: int|null, limit: int} $filters
     */
    public function available(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        array $filters
    ): AiAvailableRoomListData {
        $rooms = Room::query()
            ->visibleFor($user)
            ->with(['area:id,name', 'categories:id,name'])
            // Temporaere Raeume nur innerhalb ihres Zeitraums
            ->where(fn (Builder $query) => $query->where('temporary', false)->orWhere(
                fn (Builder $temporary) => $temporary
                    ->where(fn (Builder $q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $to))
                    ->where(
                        fn (Builder $q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $from->startOfDay())
                    )
            ))
            ->when(
                ($filters['area_id'] ?? null) !== null,
                fn (Builder $query) => $query->where('area_id', $filters['area_id'])
            )
            ->when(
                ($filters['room_category_id'] ?? null) !== null,
                fn (Builder $query) => $query->whereHas(
                    'categories',
                    fn (Builder $category) => $category->where('room_categories.id', $filters['room_category_id'])
                )
            )
            ->get();

        // Strikte Ueberschneidung: ein Termin, der genau zu Fensterbeginn endet, blockiert nicht.
        $busy = Event::query()
            ->whereIn('room_id', $rooms->modelKeys())
            ->where('start_time', '<', $to)
            ->where('end_time', '>', $from)
            ->distinct()
            ->pluck('room_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $free = $rooms
            ->reject(fn (Room $room): bool => in_array($room->id, $busy, true))
            ->sortBy([
                fn (Room $a, Room $b): int => strcmp((string) $a->area?->name, (string) $b->area?->name),
                fn (Room $a, Room $b): int => strcmp((string) $a->name, (string) $b->name),
            ])
            ->values();

        $limited = $free->take($filters['limit']);

        return new AiAvailableRoomListData(
            window: AiPeriodData::fromDates($from, $to),
            rooms: $limited->map(fn (Room $room): AiAvailableRoomData => new AiAvailableRoomData(
                id: $room->id,
                name: AiText::truncate($room->name),
                area: AiRefData::make($room->area?->id, $room->area?->name),
                categories: $room->categories
                    ->map(fn (RoomCategory $category): string => (string) AiText::truncate($category->name))
                    ->values()
                    ->all(),
                requires_request: !$this->canBookDirectly($user, $room),
            ))->all(),
            total_free: $free->count(),
            total_rooms: $rooms->count(),
            truncated: $free->count() > $limited->count(),
        );
    }

    /**
     * Dieselbe Entscheidung wie die Oberflaeche (`EventPolicy::book()`, ohne Option, ohne Planung),
     * unter dem Web-Guard, damit Hausrechte und der Admin-Durchgriff greifen.
     */
    private function canBookDirectly(User $user, Room $room): bool
    {
        return WebGuardScope::run(
            static fn (): bool => Gate::forUser($user)->allows('book', [Event::class, $room, false, false])
        );
    }
}

<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Abfrage der Termine für die Maschinen-API (GET /api/v1/events): welche Räume von wann bis wann
 * durch welches Projekt belegt sind.
 *
 * Geliefert werden nur Termine mit Raum. Planungstermine sind keine Belegung und bleiben draußen,
 * ebenso Termine in Räumen aus dem Papierkorb.
 */
class EventApiService
{
    /**
     * @param list<int> $roomIds
     * @param list<int> $areaIds
     * @return LengthAwarePaginator<int, Event>
     */
    public function paginate(
        CarbonImmutable $rangeStart,
        CarbonImmutable $rangeEnd,
        array $roomIds,
        array $areaIds,
        ?Carbon $updatedSince,
        int $perPage
    ): LengthAwarePaginator {
        return Event::query()
            ->select([
                'id',
                'eventName',
                'start_time',
                'end_time',
                'allDay',
                'occupancy_option',
                'room_id',
                'project_id',
                'event_type_id',
                'event_status_id',
                'series_id',
                'updated_at',
                'deleted_at',
            ])
            ->with([
                'room:id,name,area_id',
                'room.area:id,name',
                'project:id,name',
                'event_type:id,name',
                'eventStatus:id,name',
            ])
            ->where('is_planning', false)
            ->whereNotNull('room_id')
            ->whereHas('room')
            ->when(
                $roomIds !== [] || $areaIds !== [],
                static fn (Builder $query) => $query->where(
                    static function (Builder $query) use ($roomIds, $areaIds): void {
                        // Räume und Areale ergänzen sich: rooms=1,2&areas=3 liefert die Räume 1 und 2
                        // sowie alle Räume des Areals 3.
                        $query
                            ->when($roomIds !== [], static fn (Builder $query) => $query->whereIn('room_id', $roomIds))
                            ->when(
                                $areaIds !== [],
                                static fn (Builder $query) => $query->orWhereHas(
                                    'room',
                                    static fn (Builder $room) => $room->whereIn('area_id', $areaIds)
                                )
                            );
                    }
                )
            )
            // Jeder Termin, der den Zeitraum berührt, mit seinen echten Daten.
            ->where('start_time', '<', $rangeEnd)
            ->where('end_time', '>', $rangeStart)
            ->when(
                $updatedSince !== null,
                fn (Builder $query) => $this->restrictToChangedSince($query, $updatedSince)
            )
            ->orderBy('start_time')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Nur Termine, die seit dem Zeitpunkt angelegt, geändert oder gelöscht wurden. Mitgezählt wird
     * auch, wenn sich Projekt oder Raum geändert haben (z. B. umbenannt), weil sich dann der
     * gelieferte Name ändert. Lieber ein Termin zu viel als einer zu wenig.
     *
     * @param Builder<Event> $query
     * @return Builder<Event>
     */
    private function restrictToChangedSince(Builder $query, Carbon $updatedSince): Builder
    {
        return $query
            ->withTrashed()
            ->where(static function (Builder $query) use ($updatedSince): void {
                $query->whereNull('deleted_at')->orWhere('deleted_at', '>=', $updatedSince);
            })
            ->where(static function (Builder $query) use ($updatedSince): void {
                $query->where('updated_at', '>=', $updatedSince)
                    ->orWhere('deleted_at', '>=', $updatedSince)
                    ->orWhereHas(
                        'project',
                        static fn (Builder $project) => $project->where('updated_at', '>=', $updatedSince)
                    )
                    ->orWhereHas(
                        'room',
                        static fn (Builder $room) => $room->where('updated_at', '>=', $updatedSince)
                    );
            });
    }
}

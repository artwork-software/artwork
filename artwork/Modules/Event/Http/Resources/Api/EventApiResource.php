<?php

namespace Artwork\Modules\Event\Http\Resources\Api;

use App\Settings\EventSettings;
use Artwork\Modules\Calendar\Services\EventCalendarDays;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ein Termin in der Maschinen-API (GET /api/v1/events).
 *
 * Bewusst ohne Personendaten, Beschreibung und Kommentare: Externe Systeme bekommen nur, wer
 * welchen Raum von wann bis wann belegt. Neue Felder nur ergänzen, nie umbenennen oder entfernen —
 * die Feldnamen sind ein Vertrag mit den angebundenen Systemen.
 *
 * @mixin \Artwork\Modules\Event\Models\Event
 */
class EventApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $calendarDays = EventCalendarDays::between(
            $this->start_time->toDateTimeString(),
            $this->end_time->toDateTimeString()
        );

        return [
            'id' => $this->id,
            // Projektname, sonst Terminname, sonst Termintyp
            'title' => $this->project?->name ?: ($this->eventName ?: $this->event_type?->name),
            'project_id' => $this->project?->id,
            'project_name' => $this->project?->name,
            'event_type' => $this->event_type === null ? null : [
                'id' => $this->event_type->id,
                'name' => $this->event_type->name,
            ],
            'room_id' => $this->room_id,
            'room_name' => $this->room?->name,
            'area_id' => $this->room?->area?->id,
            'area_name' => $this->room?->area?->name,
            'start' => $this->start_time->toIso8601String(),
            'end' => $this->end_time->toIso8601String(),
            'all_day' => (bool) $this->allDay,
            'start_date' => $calendarDays->getStartDate()->toDateString(),
            'end_date' => $calendarDays->getEndDate()?->toDateString(),
            'status' => $this->resolveStatus(),
            'room_request_pending' => (bool) $this->occupancy_option,
            // Terminserie (z. B. ein Termin je Tag eines Gastspiels); null bei Einzelterminen
            'series_id' => $this->series_id,
            'updated_at' => $this->toIso8601OrNull($this->updated_at),
            'deleted_at' => $this->toIso8601OrNull($this->deleted_at),
        ];
    }

    /**
     * Das Event-Model deklariert die Zeitstempel als string, geliefert werden Carbon-Instanzen.
     */
    private function toIso8601OrNull(mixed $timestamp): ?string
    {
        return $timestamp === null ? null : Carbon::parse($timestamp)->toIso8601String();
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function resolveStatus(): ?array
    {
        if ($this->eventStatus === null || !app(EventSettings::class)->enable_status) {
            return null;
        }

        return [
            'id' => $this->eventStatus->id,
            'name' => $this->eventStatus->name,
        ];
    }
}

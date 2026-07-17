<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;

/**
 * Computes per-project BI key figures (visitors, sold tickets, revenue, capacity,
 * average price, occupancy). Single source of truth shared by export, dashboard
 * and project-list column so the numbers always agree.
 */
class BiProjectMetricsService
{
    /**
     * Full per-project KPI set for the project tab header.
     *
     * @return array<string, mixed>
     */
    public function summary(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $biData = $project->biData;
        ['value' => $visitors, 'estimated' => $visitorsEstimated] = $this->visitorsWithEstimate($project, $from, $to);
        $soldTickets = $this->soldTickets($project, $from, $to);
        $revenue = $this->revenue($project, $from, $to);
        $capacity = $this->seatsCapacity($project, $from, $to);
        $performances = $this->performances($project, $from, $to);

        return [
            'visitors' => $visitors,
            'visitors_estimated' => $visitorsEstimated,
            'visitors_not_applicable' => (bool) $biData?->visitors_not_applicable,
            'sold_tickets' => $soldTickets,
            'sold_tickets_not_applicable' => (bool) $biData?->sold_tickets_not_applicable,
            'revenue' => $revenue !== null ? round($revenue, 2) : null,
            'revenue_not_applicable' => (bool) $biData?->revenue_not_applicable,
            'avg_price' => $this->averagePrice($revenue, $soldTickets),
            'capacity' => $capacity,
            'occupancy' => $this->occupancyRate($soldTickets, $capacity),
            'performances' => $performances,
            'event_days' => $this->eventDays($project, $from, $to),
        ];
    }

    /**
     * Visitors with fallback: if no visitor figure was recorded but sold tickets
     * exist, use those as an estimate. Callers must surface the `estimated` flag
     * wherever the value is displayed.
     *
     * @return array{value: ?int, estimated: bool}
     */
    public function visitorsWithEstimate(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $visitors = $this->visitors($project, $from, $to);

        if ($visitors !== null) {
            return ['value' => $visitors, 'estimated' => false];
        }

        $biData = $project->biData;

        if (!$biData || $biData->visitors_not_applicable || $biData->sold_tickets_not_applicable) {
            return ['value' => null, 'estimated' => false];
        }

        $soldTickets = $this->soldTickets($project, $from, $to);

        if ($soldTickets === null) {
            return ['value' => null, 'estimated' => false];
        }

        return ['value' => $soldTickets, 'estimated' => true];
    }

    /**
     * Events tagged "Vorstellung"; falls back to all events when no tag matches.
     */
    public function performances(Project $project, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $events = $this->eventsInRange($project, $from, $to);
        $tagged = $events->filter(fn($event) => $this->eventHasTag($event, 'Vorstellung'));

        return $tagged->isNotEmpty() ? $tagged->count() : $events->count();
    }

    /**
     * Distinct days with events tagged "Veranstaltungstag"; falls back to all events.
     */
    public function eventDays(Project $project, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $events = $this->eventsInRange($project, $from, $to);
        $tagged = $events->filter(fn($event) => $this->eventHasTag($event, 'Veranstaltungstag'));
        $relevant = $tagged->isNotEmpty() ? $tagged : $events;

        return $relevant
            ->map(fn($event) => $event->start_time?->format('Y-m-d'))
            ->filter()
            ->unique()
            ->count();
    }

    public function visitors(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        $biData = $project->biData;

        if (!$biData || $biData->visitors_not_applicable) {
            return null;
        }

        if ($biData->visitor_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->visitors_total;
        }

        $sum = $this->sumEventData($project, 'visitors', $from, $to);

        return $sum !== null ? (int) $sum : null;
    }

    public function soldTickets(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        $biData = $project->biData;

        if (!$biData || $biData->sold_tickets_not_applicable) {
            return null;
        }

        if ($biData->sold_tickets_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->sold_tickets_total;
        }

        $sum = $this->sumEventData($project, 'sold_tickets', $from, $to);

        return $sum !== null ? (int) $sum : null;
    }

    public function revenue(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        $biData = $project->biData;

        if (!$biData || $biData->revenue_not_applicable) {
            return null;
        }

        if ($biData->revenue_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->revenue_total !== null ? (float) $biData->revenue_total : null;
        }

        return $this->sumEventData($project, 'revenue', $from, $to);
    }

    public function averagePrice(?float $revenue, ?int $soldTickets): ?float
    {
        if ($revenue === null || !$soldTickets) {
            return null;
        }

        return round($revenue / $soldTickets, 2);
    }

    /**
     * Sum of distinct project-room capacities (per-project override else room default).
     * With a date range only rooms of events in that range count — otherwise a
     * season-filtered occupancy would divide range tickets by all-time capacity.
     */
    public function seatsCapacity(Project $project, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $overrides = $project->biRoomCapacities->keyBy('room_id');

        // Ohne Range weiterhin alle Events (auch undatierte), damit Bestandsaufrufer identisch bleiben
        $events = ($from || $to) ? $this->eventsInRange($project, $from, $to) : $project->events;

        return (int) $events
            ->pluck('room')
            ->filter()
            ->unique('id')
            ->sum(function ($room) use ($overrides): int {
                $override = $overrides->get($room->id)?->capacity_override;

                return (int) ($override ?? $room->capacity ?? 0);
            });
    }

    public function occupancyRate(?int $soldTickets, int $capacity): ?float
    {
        if ($soldTickets === null || $capacity === 0) {
            return null;
        }

        return round($soldTickets / $capacity * 100, 1);
    }

    private function eventsInRange(Project $project, ?Carbon $from, ?Carbon $to)
    {
        return $project->events->filter(function ($event) use ($from, $to): bool {
            if (!$event->start_time) {
                return false;
            }

            if ($from && $event->end_time && $event->end_time->lt($from->copy()->startOfDay())) {
                return false;
            }

            if ($to && $event->start_time->gt($to->copy()->endOfDay())) {
                return false;
            }

            return true;
        });
    }

    private function eventHasTag($event, string $name): bool
    {
        $tags = $event->event_type?->biTags;

        if (!$tags) {
            return false;
        }

        return $tags->contains(
            fn($tag) => strcasecmp($tag->name_de ?? '', $name) === 0
                || strcasecmp($tag->name ?? '', $name) === 0
        );
    }

    /**
     * Sum of per-event values in range; null when no event has the field recorded
     * (distinguishes "nothing entered" from a genuine zero).
     */
    private function sumEventData(Project $project, string $field, ?Carbon $from, ?Carbon $to): ?float
    {
        $entries = $project->biEventData
            ->filter(function ($eventData) use ($from, $to): bool {
                $event = $eventData->event;

                if (!$event) {
                    return false;
                }

                // start_time ist nullable; ohne Datumsbezug aus Datumsfiltern ausschließen
                // (sonst Null-Deref bei gesetztem $from/$to).
                if (($from || $to) && !$event->start_time) {
                    return false;
                }

                if ($from && $event->start_time->lt($from->copy()->startOfDay())) {
                    return false;
                }

                if ($to && $event->start_time->gt($to->copy()->endOfDay())) {
                    return false;
                }

                return true;
            })
            ->filter(fn($eventData) => $eventData->{$field} !== null);

        if ($entries->isEmpty()) {
            return null;
        }

        return (float) $entries->sum($field);
    }
}

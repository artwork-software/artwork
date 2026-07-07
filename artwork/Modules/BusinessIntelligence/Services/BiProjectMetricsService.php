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
        $visitors = $this->visitors($project, $from, $to);
        $soldTickets = $this->soldTickets($project, $from, $to);
        $revenue = $this->revenue($project, $from, $to);
        $capacity = $this->seatsCapacity($project);
        $performances = $this->performances($project, $from, $to);

        return [
            'visitors' => $visitors,
            'sold_tickets' => $soldTickets,
            'revenue' => $revenue !== null ? round($revenue, 2) : null,
            'avg_price' => $this->averagePrice($revenue, $soldTickets),
            'capacity' => $capacity,
            'occupancy' => $this->occupancyRate($soldTickets, $capacity),
            'performances' => $performances,
            'event_days' => $this->eventDays($project, $from, $to),
        ];
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

        if (!$biData) {
            return null;
        }

        if ($biData->visitor_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->visitors_total;
        }

        return (int) $this->sumEventData($project, 'visitors', $from, $to);
    }

    public function soldTickets(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        $biData = $project->biData;

        if (!$biData) {
            return null;
        }

        if ($biData->sold_tickets_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->sold_tickets_total;
        }

        return (int) $this->sumEventData($project, 'sold_tickets', $from, $to);
    }

    public function revenue(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        $biData = $project->biData;

        if (!$biData) {
            return null;
        }

        if ($biData->revenue_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->revenue_total !== null ? (float) $biData->revenue_total : null;
        }

        return (float) $this->sumEventData($project, 'revenue', $from, $to);
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
     */
    public function seatsCapacity(Project $project): int
    {
        $overrides = $project->biRoomCapacities->keyBy('room_id');

        return (int) $project->events
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

    private function sumEventData(Project $project, string $field, ?Carbon $from, ?Carbon $to): float
    {
        return (float) $project->biEventData
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
            ->sum($field);
    }
}

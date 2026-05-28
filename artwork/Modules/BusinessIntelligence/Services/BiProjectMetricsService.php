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

    private function sumEventData(Project $project, string $field, ?Carbon $from, ?Carbon $to): float
    {
        return (float) $project->biEventData
            ->filter(function ($eventData) use ($from, $to): bool {
                $event = $eventData->event;

                if (!$event) {
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

<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Illuminate\Support\Collection;

/**
 * Die Ticketing-Komponente eines Projekts: alle Termine, deren Terminart Tickets verkaufen darf,
 * mit der Spielstätte aus tickets (Preisklassen, Plätze) als Vorgabe und dem eigenen Stand.
 */
class TicketingProjectService
{
    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingProductionService $productions,
        private readonly TicketingBillingService $billing,
    ) {
    }

    /** @return array<string, mixed> */
    public function payload(Project $project): array
    {
        $connection = $this->connections->current();
        $production = $this->productions->for($project);
        $venues = [];
        $reductions = [];
        $detail = null;
        $billingComplete = null;
        $ticketsError = null;

        if ($connection) {
            try {
                $venues = $this->connections->venues($connection);
                $reductions = $this->connections->reductions($connection);
                $detail = $this->productions->detail($production);
            } catch (TicketingConnectionException $exception) {
                $ticketsError = $exception->getMessage();
            }

            $billingComplete = $this->billing->completeness($connection);
        }

        $links = TicketingRoomLink::query()->pluck('venue_id', 'room_id');

        return [
            'connection' => [
                'connected' => $connection !== null,
                'dashboardUrl' => $connection?->dashboard_url,
                'billingComplete' => $billingComplete,
            ],
            'ticketsError' => $ticketsError,
            'hasSellingEventTypes' => EventType::query()->where('relevant_for_ticketing', true)->exists(),
            'production' => [
                'title' => $production->title,
                'description' => $production->description,
                'reductionTypeIds' => $production->reduction_type_ids,
                'heroUrl' => $production->heroUrl(),
                'linked' => $production->production_id !== null,
                'status' => $detail['status'] ?? null,
                'shopUrl' => $detail['shopUrl'] ?? null,
                'fallback' => [
                    'title' => $project->name,
                    'description' => $project->description,
                    'keyVisualUrl' => $project->key_visual_path
                        ? '/storage/keyVisual/' . $project->key_visual_path
                        : null,
                ],
            ],
            'reductions' => array_map(static fn (array $reduction): array => [
                'id' => $reduction['id'],
                'name' => $reduction['name'],
                'kind' => $reduction['kind'],
                'value' => $reduction['value'],
                'defaultEnabled' => (bool) $reduction['defaultEnabled'],
            ], $reductions),
            'events' => $this->events($project)
                ->map(fn (Event $event): array => $this->eventPayload($event, $links, $venues))
                ->values(),
        ];
    }

    /** @return Collection<int, Event> */
    private function events(Project $project): Collection
    {
        return Event::query()
            ->where('project_id', $project->id)
            ->whereHas('event_type', static fn ($query) => $query->where('relevant_for_ticketing', true))
            ->with(['event_type:id,name,hex_code,abbreviation', 'room:id,name', 'ticketingRelease.releasedBy'])
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param Collection<int, string> $links venue_id je room_id
     * @param array<string, array<string, mixed>> $venues Spielstätte je tickets-ID
     * @return array<string, mixed>
     */
    private function eventPayload(Event $event, Collection $links, array $venues): array
    {
        $venue = $event->room_id ? ($venues[$links[$event->room_id] ?? ''] ?? null) : null;
        $release = $event->ticketingRelease;

        return [
            'id' => $event->id,
            'name' => $event->eventName ?: $event->event_type?->name,
            'start' => $event->start_time?->toIso8601String(),
            'end' => $event->end_time?->toIso8601String(),
            'allDay' => (bool) $event->allDay,
            'seriesId' => $event->is_series ? $event->series_id : null,
            'eventType' => $event->event_type ? [
                'id' => $event->event_type->id,
                'name' => $event->event_type->name,
                'hexCode' => $event->event_type->hex_code,
            ] : null,
            'room' => $event->room ? ['id' => $event->room->id, 'name' => $event->room->name] : null,
            'venue' => $venue ? [
                'id' => $venue['id'],
                'name' => $venue['name'],
                'capacity' => array_sum(array_column($venue['zones'], 'capacity')),
                'zones' => $venue['zones'],
            ] : null,
            'release' => $release ? [
                'state' => $release->state,
                'classes' => $release->classes,
                'releasedAt' => $release->released_at?->toIso8601String(),
                'releasedBy' => $release->releasedBy?->full_name,
            ] : null,
        ];
    }
}

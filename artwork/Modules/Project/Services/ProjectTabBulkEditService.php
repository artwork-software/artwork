<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Event\Http\Resources\MinimalCalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;

class ProjectTabBulkEditService
{
    public function __construct(
        private readonly FilterService $filterService,
        private readonly UserService $userService,
    ) {
    }

    public function buildBulkEditPayload(Project $project, ?User $user = null): array
    {
        $user = $user ?? $this->userService->getAuthUser();
        $userBulkSortId = (int) ($user?->getAttribute('bulk_sort_id') ?? 0);
        $userCalendarFilter = $user?->userFilters()->calendarFilter()->first();

        // Events-Query mit Filtern
        $eventsQuery = $project->events()
            ->with([
                'event_type',
                'room',
                'eventStatus',
                'creator',
                'project.status',
                'project.managerUsers',
                'subEvents',
                'series',
                'shifts.craft',
                'shifts.users',
                'shifts.freelancer',
                'shifts.serviceProvider',
                'shifts.shiftsQualifications',
            ])
            ->orderBy('start_time', 'asc')
            // Eventtypen filtern (nur diese zulassen, wenn gesetzt)
            ->when(!empty($userCalendarFilter?->event_type_ids), function ($q) use ($userCalendarFilter) {
                $q->whereIn('event_type_id', $userCalendarFilter->event_type_ids);
            })
            // Raumfilter via whereHas(Room …) anwenden
            ->when(
                !empty($userCalendarFilter?->room_ids)
                || !empty($userCalendarFilter?->room_attribute_ids)
                || !empty($userCalendarFilter?->area_ids)
                || !empty($userCalendarFilter?->room_category_ids),
                function ($q) use ($userCalendarFilter) {
                    $q->whereHas('room', function ($rq) use ($userCalendarFilter) {
                        $rq->select(['id'])
                            ->unlessRoomIds($userCalendarFilter?->room_ids)
                            ->unlessRoomAttributeIds($userCalendarFilter?->room_attribute_ids)
                            ->unlessAreaIds($userCalendarFilter?->area_ids)
                            ->unlessRoomCategoryIds($userCalendarFilter?->room_category_ids);
                    });
                }
            );

        // Laden + Minimal-Resource anreichern
        $eventsUnsorted = $eventsQuery->get()->map(
            /** @return array<string,mixed> */
            fn (Event $event) => array_merge(
                $event->toArray(),
                MinimalCalendarEventResource::make($event)->resolve()
            )
        );

        // Sortierung
        $eventsSorted = match ($userBulkSortId) {
            1 => $eventsUnsorted->sortBy([
                ['roomName', 'asc'],
                ['startTime', 'asc'],
            ])->values(),
            2 => $eventsUnsorted->sortBy([
                ['eventTypeName', 'asc'],
                ['startTime', 'asc'],
            ])->values(),
            3 => $eventsUnsorted->sortBy('startTime')->values(),
            default => $eventsUnsorted,
        };

        // IDs der zuletzt bearbeiteten Events bestimmen (reuse loaded events to avoid N+1)
        $lastUpdatedEvent = $eventsUnsorted->sortByDesc('updated_at')->first();
        $lastEditEventIds = [];
        if ($lastUpdatedEvent) {
            $lastEditEventIds = $eventsUnsorted
                ->where('updated_at', $lastUpdatedEvent['updated_at'])
                ->pluck('id')
                ->toArray();
        }

        return [
            'events' => $eventsSorted->toArray(),
            'lastEditEventIds' => $lastEditEventIds,
            'user_filters' => $userCalendarFilter,
            'personalFilters' => $this->filterService->getPersonalFilter($user, UserFilterTypes::CALENDAR_FILTER->value),
            'filterOptions' => $this->filterService->getCalendarFilterDefinitions(),
        ];
    }
}


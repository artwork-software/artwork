<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Calendar\DTO\EventDTOWithVerifications;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;
use Artwork\Modules\User\Models\UserFilter;
use Illuminate\Support\Collection;

class EventPlanningCalendarService
{
    use MapRoomsToContentForCalendar;

    public function __construct(
    ) {
    }

    public function filterRoomsEvents(
        Collection $rooms,
        UserFilter $filter,
        $startDate,
        $endDate,
        null|UserCalendarSettings|UserDailyViewCalendarSettings $userCalendarSettings = null,
    ): Collection {
        $roomIds = $rooms->pluck('id');
        $events = Event::select([
                'id',
                'start_time',
                'end_time',
                'admission_time',
                'eventName',
                'description',
                'project_id',
                'event_type_id',
                'event_status_id',
                'allDay',
                'room_id',
                'user_id',
                'occupancy_option',
                'declined_room_id',
                'is_planning'
            ])
            ->with([
                // project/creator/eventStatus/event_type liest EventDTOWithVerifications
                // NICHT vom Event-Model, sondern aus den Bulk-Lookups weiter unten —
                // pro Event eager zu laden wäre doppelte Hydration ohne Nutzen.
                'room:id,name',
            ])
            ->withExists([
                'verifications as has_pending_verification' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->whereIn('room_id', $roomIds)
            ->where(function ($q) use ($startDate, $endDate): void {
                $q->whereBetween('start_time', [$startDate, $endDate])
                    ->orWhereBetween('end_time', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate): void {
                        $q->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                    });
            })
            ->unless(empty($filter->event_type_ids), function ($q) use ($filter): void {
                $q->whereIn('event_type_id', $filter->event_type_ids);
            })
            ->unless(empty($filter->event_property_ids), function ($q) use ($filter): void {
                $q->whereHas('eventProperties', function ($q) use ($filter): void {
                    $q->whereIn('event_property_id', $filter->event_property_ids);
                });
            })
            // Projektstatus-Filter (zentrale Semantik im Event-Scope)
            ->unless(empty($filter->project_state_ids), function ($q) use ($filter): void {
                $q->byProjectStateIds($filter->project_state_ids);
            })
            ->where(function ($query) use ($userCalendarSettings): void {
                // Always include planning events
                $query->where('is_planning', true);

                // If show_unplanned_events is true, also include non-planning events
                if ($userCalendarSettings?->show_unplanned_events) {
                    $query->orWhere('is_planning', false);
                }
            })
            ->orderBy('start_time')
            ->get();

        $eventTypeIds = $events->pluck('event_type_id')->unique()->filter();
        $projectIds = $events->pluck('project_id')->unique()->filter();
        $userIds = $events->pluck('user_id')->unique()->filter();
        $eventStatusIds = $events->pluck('event_status_id')->unique()->filter();
        $eventIds = $events->pluck('id');

        $users = User::whereIn('id', $userIds)
            ->select(['id', 'first_name', 'last_name', 'position', 'email'])
            ->get()->keyBy('id');

        $projects = Project::whereIn('id', $projectIds)
            ->select(['id', 'name', 'state', 'artists', 'is_group', 'color', 'icon'])
            ->with(['status:id,name,color', 'managerUsers:id,first_name,last_name,position,email,profile_photo_path', 'managerUsers.departments:id', 'groups', 'categories'])
            ->get()->keyBy('id');

        $eventTypes = EventType::whereIn('id', $eventTypeIds)
            ->select(['id', 'name', 'abbreviation', 'hex_code'])
            ->get()
            ->keyBy('id');

        $eventStatuses = EventStatus::whereIn('id', $eventStatusIds)
            ->select(['id', 'name', 'color'])
            ->get()
            ->keyBy('id');

        // Bulk-load rejected verifications to avoid N+1
        $verificationsByEvent = EventVerification::whereIn('event_id', $eventIds)
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->with('verifier')
            ->get()
            ->groupBy('event_id');

        $eventDTOs = $events->map(fn ($event) => EventDTOWithVerifications::fromModel(
            $event,
            $userCalendarSettings,
            $projects,
            $eventTypes,
            $users,
            $eventStatuses,
            $verificationsByEvent,
        ))->groupBy('roomId');

        foreach ($rooms as $room) {
            $room->events = $eventDTOs[$room->id] ?? collect();
        }

        if ($userCalendarSettings?->work_shifts) {
            $this->attachStandaloneShiftsToRooms($rooms, $startDate, $endDate, $filter);
        } else {
            foreach ($rooms as $room) {
                $room->shifts = collect();
            }
        }

        return $rooms;
    }
}

<?php

namespace Artwork\Modules\Event\Services;

use App\Enums\NotificationConstEnum;
use App\Events\OccupancyUpdated;
use App\Support\Services\NewHistoryService;
use App\Support\Services\NotificationService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\EventComment\Services\EventCommentService;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\PresetShift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\SubEvents\Services\SubEventService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

readonly class EventService
{
    public function __construct(
        private EventRepository $eventRepository,
        private ShiftService $shiftService,
        private ShiftsQualificationsService $shiftsQualificationsService,
        private ShiftQualificationService $shiftQualificationService,
        private TimelineService $timelineService,
        private NotificationService $notificationService,
        private SubEventService $subEventService,
        private EventCommentService $eventCommentService,
        private ProjectTabService $projectTabService
    ) {
    }

    public function importShiftPreset(Event $event, ShiftPreset $shiftPreset): void
    {
        $this->timelineService->forceDeleteTimelines($event->timelines);
        foreach ($shiftPreset->timeline as $shiftPresetTimeline) {
            $this->timelineService->createFromShiftPresetTimeline($shiftPresetTimeline, $event->id);
        }

        $this->shiftService->forceDeleteShifts($event->shifts);
        /** @var PresetShift $presetShift */
        foreach ($shiftPreset->shifts as $presetShift) {
            $shift = $this->shiftService->createFromShiftPresetShiftForEvent($presetShift, $event->id);

            /** @var PresetShiftShiftsQualifications $presetShiftShiftsQualification */
            foreach ($presetShift->shiftsQualifications as $presetShiftShiftsQualification) {
                if (
                    !$this->shiftQualificationService->isStillAvailable(
                        $presetShiftShiftsQualification->shift_qualification_id
                    )
                ) {
                    continue;
                }

                $this->shiftsQualificationsService->createShiftsQualificationForShift(
                    $shift->id,
                    [
                        'shift_qualification_id' => $presetShiftShiftsQualification->shift_qualification_id,
                        'value' => $presetShiftShiftsQualification->value
                    ]
                );
            }
        }
    }

    public function importShiftPresetForEventsOfProjectByEventType(
        ShiftPreset $shiftPreset,
        int $projectId
    ): void {
        foreach (
            $this->eventRepository->getEventsByProjectIdAndEventTypeId(
                $projectId,
                $shiftPreset->event_type_id
            ) as $eventByProjectIdAndEventTypeId
        ) {
            $this->importShiftPreset($eventByProjectIdAndEventTypeId, $shiftPreset);
        }
    }

    public function delete(Event $event): void
    {
        if (!empty($event->project_id)) {
            $projectHistory = new NewHistoryService(Project::class);
            $projectHistory->createHistory($event->project->id, 'Schedule deleted');
        }

        $this->createEventDeletedNotificationsForProjectManagers($event);
        $this->createEventDeletedNotification($event);

        $this->eventCommentService->deleteEventComments($event->comments);
        $this->timelineService->deleteTimelines($event->timelines);
        $this->shiftService->deleteShifts($event->shifts);
        $this->subEventService->deleteSubEvents($event->subEvents);

        broadcast(new OccupancyUpdated())->toOthers();

        $this->notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

        $this->eventRepository->delete($event);
    }

    public function deleteAll(Collection|array $events): void
    {
        /** @var Event $event */
        foreach ($events as $event) {
            if (!empty($event->project_id)) {
                $projectHistory = new NewHistoryService(Project::class);
                $projectHistory->createHistory($event->project->id, 'Schedule deleted');
            }

            $this->createEventDeletedNotificationsForProjectManagers($event);
            $this->createEventDeletedNotification($event);

            $this->eventCommentService->deleteEventComments($event->comments);
            $this->timelineService->deleteTimelines($event->timelines);
            $this->shiftService->deleteShifts($event->shifts);
            $this->subEventService->deleteSubEvents($event->subEvents);

            broadcast(new OccupancyUpdated())->toOthers();

            $this->notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

            $this->eventRepository->delete($event);
        }
    }

    public function restore(Event $event): void
    {
        $this->eventRepository->restore($event);
        if (!empty($event->project_id)) {
            $projectHistory = new NewHistoryService(Project::class);
            $projectHistory->createHistory($event->project_id, 'Schedule deleted');
        }
        $this->eventCommentService->restoreEventComments($event->comments()->onlyTrashed()->get());
        $this->timelineService->restoreTimelines($event->timelines()->onlyTrashed()->get());
        $this->shiftService->restoreShifts($event->shifts()->onlyTrashed()->get());
        $this->subEventService->restoreSubEvents($event->subEvents()->onlyTrashed()->get());

        broadcast(new OccupancyUpdated())->toOthers();
    }

    public function forceDeleteAll(Collection|array $events): void
    {
        /** @var Event $event */
        foreach ($events as $event) {
            $this->eventCommentService->deleteEventComments($event->comments);
            $this->timelineService->forceDeleteTimelines($event->timelines);
            $this->shiftService->forceDeleteShifts($event->shifts);
            $this->subEventService->forceDeleteSubEvents($event->subEvents);

            $this->notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

            $this->eventRepository->forceDelete($event);
        }
    }

    public function restoreAll(Collection|array $events): void
    {
        /** @var Event $event */
        foreach ($events as $event) {
            $this->eventRepository->restore($event);
            if (!empty($event->project_id)) {
                $projectHistory = new NewHistoryService(Project::class);
                $projectHistory->createHistory($event->project_id, 'Schedule deleted');
            }

            //$this->createEventDeletedNotificationsForProjectManagers($event);
            //$this->createEventDeletedNotification($event);
            $this->eventCommentService->restoreEventComments($event->comments()->onlyTrashed()->get());
            $this->timelineService->restoreTimelines($event->timelines()->onlyTrashed()->get());
            $this->shiftService->restoreShifts($event->shifts()->onlyTrashed()->get());
            $this->subEventService->restoreSubEvents($event->subEvents()->onlyTrashed()->get());

            broadcast(new OccupancyUpdated())->toOthers();

            //$this->notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);
        }
    }
    private function createEventDeletedNotificationsForProjectManagers(Event $event): void
    {
        if (is_null($event->project) || $event->project->managerUsers->isEmpty()) {
            return;
        }

        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);

        foreach ($event->project->managerUsers as $projectManager) {
            if ($projectManager->id === $event->creator) {
                continue;
            }

            $notificationTitle = __('notification.event.deleted', [], $projectManager->language);
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage([
                'id' => random_int(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ]);
            $this->notificationService->setDescription([
                1 => [
                    'type' => 'link',
                    'title' => $event->room?->name ?? '',
                    'href' => $event->room ? route('rooms.show', $event->room->id) : null
                ],
                2 => [
                    'type' => 'string',
                    'title' => $event->event_type->name . ', ' . $event->eventName,
                    'href' => null
                ],
                3 => [
                    'type' => 'link',
                    'title' => $event->project?->name ?? '',
                    'href' => $event->project ? route(
                        'projects.tab',
                        [
                            $event->project->id,
                            $this->projectTabService->findFirstProjectTabWithCalendarComponent()?->id
                        ]
                    ) : null
                ],
                4 => [
                    'type' => 'string',
                    'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                        Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                    'href' => null
                ]
            ]);
            $this->notificationService->setNotificationTo($projectManager);
            $this->notificationService->createNotification();
        }
    }

    private function createEventDeletedNotification(Event $event): void
    {
        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);
        $notificationTitle = __('notification.event.deleted', [], $event->creator->language);
        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setBroadcastMessage([
            'id' => random_int(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ]);
        $this->notificationService->setDescription([
            1 => [
                'type' => 'link',
                'title' => $event->room->name ?? '',
                'href' => $event->room ? route('rooms.show', $event->room->id) : null
            ],
            2 => [
                'type' => 'string',
                'title' => $event->event_type->name . ', ' . $event->eventName,
                'href' => null
            ],
            3 => [
                'type' => 'link',
                'title' => $event->project?->name ?? '',
                'href' => $event->project ? route(
                    'projects.tab',
                    [
                        $event->project->id,
                        $this->projectTabService->findFirstProjectTabWithCalendarComponent()?->id
                    ]
                ) : null
            ],
            4 => [
                'type' => 'string',
                'title' => Carbon::parse($event->start_time)->translatedFormat('d.m.Y H:i') . ' - ' .
                    Carbon::parse($event->end_time)->translatedFormat('d.m.Y H:i'),
                'href' => null
            ]
        ]);
        $this->notificationService->setNotificationTo($event->creator);
        $this->notificationService->createNotification();
    }
}

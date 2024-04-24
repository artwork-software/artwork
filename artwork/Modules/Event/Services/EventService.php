<?php

namespace Artwork\Modules\Event\Services;

use App\Enums\NotificationConstEnum;
use App\Events\OccupancyUpdated;
use App\Support\Services\NotificationService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\EventComment\Services\EventCommentService;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\PresetShift\Models\PresetShiftShiftsQualifications;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Shift\Services\ShiftFreelancerService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftServiceProviderService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\ShiftPreset\Models\ShiftPreset;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\SubEvents\Services\SubEventService;
use Artwork\Modules\Timeline\Services\TimelineService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

readonly class EventService
{
    public function __construct(private EventRepository $eventRepository)
    {
    }

    public function importShiftPreset(
        Event $event,
        ShiftPreset $shiftPreset,
        TimelineService $timelineService,
        ShiftService $shiftService,
        ShiftQualificationService $shiftQualificationService,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        $timelineService->forceDeleteTimelines($event->timelines);
        foreach ($shiftPreset->timeline as $shiftPresetTimeline) {
            $timelineService->createFromShiftPresetTimeline($shiftPresetTimeline, $event->id);
        }

        $shiftService->forceDeleteShifts($event->shifts);
        /** @var PresetShift $presetShift */
        foreach ($shiftPreset->shifts as $presetShift) {
            $shift = $shiftService->createFromShiftPresetShiftForEvent($presetShift, $event->id);

            /** @var PresetShiftShiftsQualifications $presetShiftShiftsQualification */
            foreach ($presetShift->shiftsQualifications as $presetShiftShiftsQualification) {
                if (
                    !$shiftQualificationService->isStillAvailable(
                        $presetShiftShiftsQualification->shift_qualification_id
                    )
                ) {
                    continue;
                }

                $shiftsQualificationsService->createShiftsQualificationForShift(
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
        int $projectId,
        TimelineService $timelineService,
        ShiftService $shiftService,
        ShiftQualificationService $shiftQualificationService,
        ShiftsQualificationsService $shiftsQualificationsService
    ): void {
        foreach (
            $this->eventRepository->getEventsByProjectIdAndEventTypeId(
                $projectId,
                $shiftPreset->event_type_id
            ) as $eventByProjectIdAndEventTypeId
        ) {
            $this->importShiftPreset(
                $eventByProjectIdAndEventTypeId,
                $shiftPreset,
                $timelineService,
                $shiftService,
                $shiftQualificationService,
                $shiftsQualificationsService
            );
        }
    }

    public function delete(
        Event $event,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): void {
        if (!empty($event->project_id)) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($event->project->id)
                    ->setTranslationKey('Schedule deleted')
            );
        }

        $this->createEventDeletedNotificationsForProjectManagers($event, $notificationService, $projectTabService);
        $this->createEventDeletedNotification($event, $notificationService, $projectTabService);

        $eventCommentService->deleteEventComments($event->comments);
        $timelineService->deleteTimelines($event->timelines);
        $shiftService->deleteShifts(
            $event->shifts,
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService
        );
        $subEventService->deleteSubEvents($event->subEvents);

        broadcast(new OccupancyUpdated())->toOthers();

        $notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

        $this->eventRepository->delete($event);
    }

    public function deleteAll(
        Collection|array $events,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): void {
        /** @var Event $event */
        foreach ($events as $event) {
            if (!empty($event->project_id)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($event->project->id)
                        ->setTranslationKey('Schedule deleted')
                );
            }

            $this->createEventDeletedNotificationsForProjectManagers($event, $notificationService, $projectTabService);
            $this->createEventDeletedNotification($event, $notificationService, $projectTabService);

            $eventCommentService->deleteEventComments($event->comments);
            $timelineService->deleteTimelines($event->timelines);
            $shiftService->deleteShifts(
                $event->shifts,
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService
            );
            $subEventService->deleteSubEvents($event->subEvents);

            broadcast(new OccupancyUpdated())->toOthers();

            $notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

            $this->eventRepository->delete($event);
        }
    }

    public function restore(
        Event $event,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService
    ): void {
        $this->eventRepository->restore($event);
        if (!empty($event->project_id)) {
            $changeService->saveFromBuilder(
                $changeService
                    ->createBuilder()
                    ->setModelClass(Project::class)
                    ->setModelId($event->project->id)
                    ->setTranslationKey('Schedule restored')
            );
        }
        $eventCommentService->restoreEventComments($event->comments()->onlyTrashed()->get());
        $timelineService->restoreTimelines($event->timelines()->onlyTrashed()->get());
        $shiftService->restoreShifts(
            $event->shifts()->onlyTrashed()->get(),
            $shiftsQualificationsService,
            $shiftUserService,
            $shiftFreelancerService,
            $shiftServiceProviderService
        );
        $subEventService->restoreSubEvents($event->subEvents()->onlyTrashed()->get());

        broadcast(new OccupancyUpdated())->toOthers();
    }

    public function forceDeleteAll(
        Collection|array $events,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService,
        NotificationService $notificationService
    ): void {
        /** @var Event $event */
        foreach ($events as $event) {
            $eventCommentService->deleteEventComments($event->comments);
            $timelineService->forceDeleteTimelines($event->timelines);
            $shiftService->forceDeleteShifts($event->shifts);
            $subEventService->forceDeleteSubEvents($event->subEvents);

            $notificationService->deleteUpsertRoomRequestNotificationByEventId($event->id);

            $this->eventRepository->forceDelete($event);
        }
    }

    public function restoreAll(
        Collection|array $events,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService,
        ChangeService $changeService,
        EventCommentService $eventCommentService,
        TimelineService $timelineService,
        ShiftService $shiftService,
        SubEventService $subEventService
    ): void {
        /** @var Event $event */
        foreach ($events as $event) {
            $this->eventRepository->restore($event);
            if (!empty($event->project_id)) {
                $changeService->saveFromBuilder(
                    $changeService
                        ->createBuilder()
                        ->setModelClass(Project::class)
                        ->setModelId($event->project->id)
                        ->setTranslationKey('Schedule restored')
                );
            }

            $eventCommentService->restoreEventComments($event->comments()->onlyTrashed()->get());
            $timelineService->restoreTimelines($event->timelines()->onlyTrashed()->get());
            $shiftService->restoreShifts(
                $event->shifts()->onlyTrashed()->get(),
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService
            );
            $subEventService->restoreSubEvents($event->subEvents()->onlyTrashed()->get());

            broadcast(new OccupancyUpdated())->toOthers();
        }
    }

    private function createEventDeletedNotificationsForProjectManagers(
        Event $event,
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): void {
        if (is_null($event->project) || $event->project->managerUsers->isEmpty()) {
            return;
        }

        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);

        foreach ($event->project->managerUsers as $projectManager) {
            if ($projectManager->id === $event->creator->id) {
                continue;
            }

            $notificationTitle = __('notification.event.deleted', [], $projectManager->language);
            $notificationService->setTitle($notificationTitle);
            $notificationService->setBroadcastMessage([
                'id' => random_int(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ]);
            $notificationService->setDescription([
                1 => [
                    'type' => 'link',
                    'title' => $event->room?->name,
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
                            $projectTabService->findFirstProjectTabWithCalendarComponent()?->id
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
            $notificationService->setNotificationTo($projectManager);
            $notificationService->createNotification();
        }
    }

    private function createEventDeletedNotification(
        Event $event,
        NotificationService $notificationService,
        ProjectTabService $projectTabService
    ): void {
        $notificationService->setIcon('blue');
        $notificationService->setPriority(1);
        $notificationService->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_ROOM_ANSWER);
        $notificationTitle = __('notification.event.deleted', [], $event->creator->language);
        $notificationService->setTitle($notificationTitle);
        $notificationService->setBroadcastMessage([
            'id' => random_int(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ]);
        $notificationService->setDescription([
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
                        $projectTabService->findFirstProjectTabWithCalendarComponent()?->id
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
        $notificationService->setNotificationTo($event->creator);
        $notificationService->createNotification();
    }
}

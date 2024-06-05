<?php

namespace Artwork\Migrating\Jobs;

use Artwork\Migrating\Contracts\DataAggregator;
use Artwork\Migrating\ImportConfig;
use Artwork\Migrating\Models\EventImportModel;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class ImportEvent
{
    use Queueable;
    use InteractsWithQueue;

    private RoomService $roomService;
    private EventService $eventService;
    private Dispatcher $dispatcher;
    private EventTypeService $eventTypeService;

    public function __construct(
        private readonly ImportConfig $config,
        private readonly DataAggregator $dataAggregator,
        private readonly EventImportModel $event,
        private readonly Project $project,
    ) {
        $this->roomService = app()->get(RoomService::class);
        $this->eventTypeService = app()->get(EventTypeService::class);
        $this->eventService = app()->get(EventService::class);
        $this->dispatcher = app()->get(Dispatcher::class);
    }

    public function handle(): void
    {
        if (!$room = $this->getRoom()) {
            logger()->debug('Room not found, using fallback room');
            $room = $this->createRoomOrGetFallbackRoom();
        }

        if (!$eventType = $this->getEventType()) {
            logger()->debug('Event type not found, using fallback event type');
            $eventType = $this->createEventTypeOrGetFallbackEventType();
        }

        try {
            DB::beginTransaction();
            $event = new Event();
            $event->project()->associate($this->project);
            $event->room()->associate($room);
            $event->name = $this->event->name;
            $event->eventName = $this->event->name;
            $event->description = $this->event->description;
            $event->start_time = $this->parseStartTime();
            $event->end_time = $this->parseEndTime();
            $event->allDay = $this->eventIsAllday();
            $event->event_type()->associate($eventType);
            $event->creator()->associate(User::first());
            $this->eventService->save($event);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Event creation failed. Project: ' . $this->project->name);
            throw $e;
        }
    }

    private function eventIsAllDay(): bool
    {
        return $this->config->emptyStartTimeIsWholeDay() && !$this->event->start;
    }

    private function parseStartTime(): Carbon
    {
        if ($this->config->emptyStartTimeIsWholeDay()) {
            return Carbon::parse($this->event->date)->startOfDay();
        }

        return Carbon::parse($this->event->date . ' ' . $this->event->start);
    }

    private function parseEndTime(): Carbon
    {
        if ($this->config->emptyEndTimeIsEndOfDay()) {
            return Carbon::parse($this->event->date)->endOfDay();
        }

        return Carbon::parse($this->event->date . ' ' . $this->event->end);
    }

    private function createRoomOrGetFallbackRoom(): Room
    {
        if ($this->config->shouldCreateRoom()) {
            $this->dispatcher->dispatchSync(new ImportRoom($this->dataAggregator->findRoom($this->event->room)));
            return $this->getRoom();
        }

        return $this->roomService->getFallbackRoom();
    }

    private function createEventTypeOrGetFallbackEventType(): EventType
    {
        if ($this->config->shouldCreateEventType()) {
            $this->dispatcher->dispatchSync(
                new ImportEventType($this->dataAggregator->findEventType($this->event->eventType))
            );
            return $this->getEventType();
        }

        return $this->eventTypeService->getFallbackEventType();
    }

    private function getRoom(): ?Room
    {
        return $this->roomService->findByName($this->dataAggregator->findRoom($this->event->room)?->name);
    }

    private function getEventType(): ?EventType
    {
        return $this->eventTypeService->findByName(
            $this->dataAggregator->findEventType($this->event->eventType)?->name
        );
    }
}

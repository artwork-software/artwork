<?php

namespace Artwork\Modules\Timeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventTimelineService;
use Artwork\Modules\Shift\Models\ShiftPresetTimeline;
use Illuminate\Http\Request;

/**
 * Ablaufplan („Timeline") eines Termins. Die Aktionen lagen historisch im ShiftController,
 * weil Schichten früher an Terminen hingen und über deren Ablaufplan geplant wurden. Diese
 * Verbindung gibt es nicht mehr — der Ablaufplan ist Inhalt des Termins und wird deshalb
 * über EventPolicy::editTimeline autorisiert (Schreibrecht am Projekt, nicht Dienstplanung).
 */
class EventTimelineController extends Controller
{
    public function __construct(
        private readonly EventTimelineService $eventTimelineService,
    ) {
    }

    public function update(Event $event, Request $request): void
    {
        $this->authorize('editTimeline', $event);

        $this->eventTimelineService->updateTimeLines($event, $request->get('dataset'));

        $this->broadcastEvent($event);
    }

    public function store(Event $event, Request $request): void
    {
        $this->authorize('editTimeline', $event);

        $this->eventTimelineService->addTimeLines($event, $request->get('dataset'));

        $this->broadcastEvent($event);
    }

    public function importPreset(Event $event, ShiftPresetTimeline $shiftPresetTimeline): void
    {
        $this->authorize('editTimeline', $event);

        $this->eventTimelineService->importTimelinePreset($event, $shiftPresetTimeline);
    }

    public function storePresetFromEvent(Event $event, Request $request): void
    {
        $this->authorize('editTimeline', $event);

        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $this->eventTimelineService->storeTimelinePresetFromEvent($event, $validated['name']);
    }

    private function broadcastEvent(Event $event): void
    {
        $freshEvent = $event->fresh();

        broadcast(new EventCreated($freshEvent, $freshEvent?->room_id));
    }
}

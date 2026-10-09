<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppEventRequest;
use Artwork\Modules\AppApi\Services\AppSystemComponentService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppProjectEventController extends Controller
{
    public function __construct(
        private readonly AppSystemComponentService $systemComponentService,
    ) {
    }

    /**
     * One event with the editor's picker options — deeplinks do not have to
     * load the whole tab payload to edit a single event.
     */
    public function show(Request $request, Project $project, Event $event): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json([
            'event' => $this->systemComponentService->eventPayload($request->user(), $event),
            ...$this->systemComponentService->eventEditorOptions(),
        ]);
    }

    public function store(AppEventRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $room = $request->validated('room_id') !== null
            ? Room::query()->find($request->validated('room_id'))
            : null;
        $this->authorize('create', [Event::class, $room]);
        if ($room !== null) {
            // The app always books directly, never as an option/request.
            $this->authorize('book', [Event::class, $room]);
        }

        $event = Event::create([
            ...$this->eventAttributes($request),
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'event_status_id' => EventStatus::query()->where('default', true)->value('id'),
            'is_planning' => false,
            'occupancy_option' => false,
            'audience' => false,
            'is_loud' => false,
            'is_series' => false,
            'accepted' => false,
            'option_string' => '',
        ]);

        return response()->json(
            ['event' => $this->systemComponentService->eventPayload($request->user(), $event)],
            201,
        );
    }

    public function update(AppEventRequest $request, Project $project, Event $event): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('update', $event);

        $event->update($this->eventAttributes($request));

        return response()->json([
            'event' => $this->systemComponentService->eventPayload($request->user(), $event),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventAttributes(AppEventRequest $request): array
    {
        return [
            'eventName' => $request->validated('name'),
            // The casts serialize with a display format but do not normalize
            // ISO 8601 input for the database — parse explicitly.
            'start_time' => Carbon::parse($request->validated('start')),
            'end_time' => Carbon::parse($request->validated('end')),
            'allDay' => $request->validated('all_day'),
            'room_id' => $request->validated('room_id'),
            'event_type_id' => $request->validated('event_type_id'),
        ];
    }
}

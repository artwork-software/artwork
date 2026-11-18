<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppEventRequest;
use Artwork\Modules\AppApi\Services\AppSystemComponentService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Services\EventChangeApprovalService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomRequestNotificationService;
use Artwork\Modules\Ticketing\Services\TicketingLock;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppProjectEventController extends Controller
{
    public function __construct(
        private readonly AppSystemComponentService $systemComponentService,
        private readonly RoomRequestNotificationService $roomRequestNotificationService,
        private readonly TicketingLock $ticketingLock,
        private readonly EventChangeApprovalService $changeApprovalService,
    ) {
    }

    /**
     * One event with the editor's picker options — deeplinks do not have to
     * load the whole tab payload to edit a single event. A date on sale tells
     * the editor to ask before moving it (TicketingLock::MOVE_CONFIRMED_HEADER).
     */
    public function show(Request $request, Project $project, Event $event): JsonResponse
    {
        $this->authorize('view', $project);
        // Planungstermine nur mit Planungskalender-Recht (EventPolicy::view).
        $this->authorize('view', $event);

        return response()->json([
            'event' => $this->systemComponentService->eventPayload($request->user(), $event),
            'ticketing_on_sale' => $this->ticketingLock->isOnSale($event),
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
        } else {
            // Like EventController::storeEvent: events without a room need
            // their own right (calendar, not planning calendar).
            $this->authorize('bookWithoutRoom', [Event::class, false]);
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

    /**
     * Room and time changes follow the web (EventController::update): without
     * the right to book the room directly, a room change or a period beyond
     * the confirmed booking makes the event a room request
     * (EventChangeApprovalService) and the room admins are notified. A pending request whose details
     * changed refreshes the admins' notification. Moving a date that is on
     * sale needs the ticketing confirmation header (TicketingLock, 422).
     */
    public function update(AppEventRequest $request, Project $project, Event $event): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('view', $event);
        $this->authorize('update', $event);

        $approvalBefore = $this->changeApprovalService->snapshot($event);
        $event->fill($this->eventAttributes($request));
        // One save, so a ticketing lock rejects the whole change
        $this->changeApprovalService->evaluate($request->user(), $event, $approvalBefore);
        $event->save();
        // The policy loaded the OLD room — the request must reach the new room's admins
        if ($event->wasChanged('room_id')) {
            $event->unsetRelation('room');
        }

        if (!$event->is_planning && $event->occupancy_option && $event->room_id !== null) {
            $this->roomRequestNotificationService->notifyRoomAdmins($event);
        }

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

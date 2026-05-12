<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventVerificationRequest;
use App\Http\Requests\UpdateEventVerificationRequest;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Http\Resources\EventShowResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventVerificationController extends Controller
{

    public function __construct(
        private readonly EventVerificationService $eventVerificationService,
        private readonly AuthManager $authManager,
        private readonly EventService $eventService,
        private readonly ProjectTabService $projectTabService,
    ) {
    }

    /**
     * Tab 1: Incoming requests (room booking requests + verification requests)
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function index()
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $isAdmin = $user->hasRole('artwork admin');
        $hasGlobalCreate = $user->can(PermissionEnum::CREATE_EVENTS_WITHOUT_REQUEST->value);

        // --- Section 1: Room booking requests ---
        $canSeeRoomRequests = $isAdmin || $hasGlobalCreate;
        $adminRoomIds = collect();
        $roomRequestScopeNames = [];

        if (!$canSeeRoomRequests) {
            $adminRoomIds = Room::whereHas('admins', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id');
            $canSeeRoomRequests = $adminRoomIds->isNotEmpty();
        }

        $roomBookingRequests = collect();
        if ($canSeeRoomRequests) {
            $query = Event::where('occupancy_option', true)
                ->whereNotNull('room_id')
                ->with(['room', 'event_type', 'project', 'creator'])
                ->orderBy('start_time');

            if (!$isAdmin && !$hasGlobalCreate) {
                $query->whereIn('room_id', $adminRoomIds);
                $roomRequestScopeNames = Room::whereIn('id', $adminRoomIds)
                    ->pluck('name')
                    ->toArray();
            }

            $roomBookingRequests = EventShowResource::collection($query->get())
                ->resolve();
            $roomBookingRequests = collect($roomBookingRequests)->groupBy(fn ($e) => $e['room']['id'] ?? 0);
        }

        // --- Section 2: Verification requests ---
        $canSeeVerifications = $isAdmin;
        $verificationScopeNames = [];

        if (!$canSeeVerifications) {
            $verifierEventTypeIds = EventType::where('specific_verifier_id', $user->id)
                ->orWhereHas('verifiers', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id');
            $canSeeVerifications = $verifierEventTypeIds->isNotEmpty();
            if ($canSeeVerifications) {
                $verificationScopeNames = EventType::whereIn('id', $verifierEventTypeIds)
                    ->pluck('name')
                    ->toArray();
            }
        }

        $verificationRequests = collect();
        if ($canSeeVerifications) {
            $query = EventVerification::where('status', 'pending')
                ->with(['event.room', 'event.event_type', 'event.project', 'verifier', 'requester'])
                ->whereHas('event');

            if (!$isAdmin) {
                $query->where('verifier_id', $user->id);
            }

            $verificationRequests = $query->orderBy('created_at')
                ->get()
                ->groupBy(fn ($v) => $v->event->event_type_id ?? 0);
        }

        return Inertia::render('EventVerification/Index', [
            'roomBookingRequests' => $roomBookingRequests,
            'canSeeRoomRequests' => $canSeeRoomRequests,
            'roomRequestScope' => ($isAdmin || $hasGlobalCreate) ? 'all' : $roomRequestScopeNames,
            'verificationRequests' => $verificationRequests,
            'canSeeVerifications' => $canSeeVerifications,
            'verificationScope' => $isAdmin ? 'all' : $verificationScopeNames,
        ]);
    }

    /**
     * Tab 2: Requests sent by me
     */
    public function sent()
    {
        $myRequestPaginate = request()?->integer('myRequestsPerPage', 5);
        /** @var User $user */
        $user = $this->authManager->user();

        $myRoomRequests = Event::where('user_id', $user->id)
            ->where('occupancy_option', true)
            ->with(['room', 'event_type', 'project', 'creator', 'eventStatus', 'eventProperties', 'subEvents', 'series'])
            ->orderBy('start_time', 'desc')
            ->get();

        $myRoomRequestsForEdit = $myRoomRequests->map(fn (Event $event) => [
            'id' => $event->id,
            'user_id' => $event->user_id,
            'start' => $event->start_time?->format('Y-m-d H:i'),
            'end' => $event->end_time?->format('Y-m-d H:i'),
            'eventName' => $event->eventName,
            'description' => $event->description,
            'project' => $event->project ? [
                'id' => $event->project->id,
                'name' => $event->project->name,
            ] : null,
            'eventType' => $event->event_type,
            'allDay' => (bool) $event->allDay,
            'roomId' => $event->room_id,
            'roomName' => $event->room?->name,
            'declinedRoomId' => $event->declined_room_id,
            'created_by' => $event->creator,
            'is_series' => (bool) $event->is_series,
            'occupancy_option' => (bool) $event->occupancy_option,
            'eventStatus' => $event->eventStatus,
            'eventProperties' => $event->eventProperties,
            'subEvents' => $event->subEvents,
            'series' => $event->is_series ? $event->series : null,
            'option_string' => $event->option_string,
            'isPlanning' => (bool) ($event->is_planning ?? false),
        ]);

        return Inertia::render('EventVerification/Sent', [
            'myRequests' => $this->eventVerificationService->getAllRequestedByUser($user, $myRequestPaginate),
            'plannedEvents' => $this->eventVerificationService->getPlannedEvents($user),
            'myRoomRequests' => EventShowResource::collection($myRoomRequests)->resolve(),
            'myRoomRequestsForEdit' => $myRoomRequestsForEdit,
            'rooms' => Room::select(['id', 'name', 'area_id', 'order', 'everyone_can_book'])->get(),
            'eventTypes' => EventType::all(),
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'first_project_calendar_tab_id' => $this->projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'event_properties' => EventProperty::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Event $event)
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $this->eventVerificationService->requestVerification($event, $user);
        broadcast(new EventCreated($event->fresh(), $event->room_id));
    }

    public function approved(EventVerification $eventVerification): void
    {
        $this->eventVerificationService->approveVerification($eventVerification);
        $event = $eventVerification->event;
        broadcast(new EventCreated($event, $event->room_id));
    }

    public function rejected(EventVerification $eventVerification, Request $request): void
    {
        $this->eventVerificationService->rejectVerification($eventVerification, $request->get('rejection_reason', ''));
        $event = $eventVerification->event;
        broadcast(new EventCreated($event, $event->room_id));
    }

    public function cancelVerification(Event $event): void
    {
        $this->eventVerificationService->cancelVerification($event);
        broadcast(new EventCreated($event, $event->room_id));
    }

    public function approvedByEvent(Event $event): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $this->eventVerificationService->approveVerificationByEvent($event, $user);
        broadcast(new EventCreated($event->fresh(), $event->room_id));
    }

    public function rejectByEvent(Event $event, Request $request): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $rejectionReason = $request->get('rejection_reason', '');
        $this->eventVerificationService->rejectVerificationByEvent($event, $user, $rejectionReason);
        broadcast(new EventCreated($event->fresh(), $event->room_id));
    }

    public function rejectByEvents(Request $request): void
    {
        $events = $request->collect('events', []);
        foreach ($events as $eventId) {
            /** @var Event $event */
            $event = $this->eventService->findEventById($eventId);
            $this->rejectByEvent($event, $request);
        }
    }

    public function approvedByEvents(Request $request): void
    {
        $events = $request->collect('events', []);
        foreach ($events as $eventId) {
            /** @var Event $event */
            $event = $this->eventService->findEventById($eventId);
            $this->approvedByEvent($event);
        }
    }

    public function requestVerification(Request $request): void
    {
        $events = $request->collect('events', []);
        foreach ($events as $eventId) {
            /** @var Event $event */
            $event = $this->eventService->findEventById($eventId);
            $this->store($event);
        }
    }

    /**
     * Request verification for all planning events in a project
     */
    public function requestVerificationForProject(Request $request, $project): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $planningEvents = Event::where('project_id', $project)
            ->where('is_planning', true)
            ->get();

        foreach ($planningEvents as $event) {
            $this->eventVerificationService->requestVerification($event, $user);
        }

        $refreshedEvents = Event::whereIn('id', $planningEvents->pluck('id'))->get();
        foreach ($refreshedEvents as $event) {
            broadcast(new EventCreated($event, $event->room_id));
        }
    }

    /**
     * Convert all events of a project to planning events
     */
    public function convertToPlanning(Request $request, $project): void
    {
        $events = Event::where('project_id', $project)->get();
        $eventIds = $events->pluck('id');

        Event::whereIn('id', $eventIds)->update(['is_planning' => true]);

        $refreshedEvents = Event::whereIn('id', $eventIds)->get();
        foreach ($refreshedEvents as $event) {
            broadcast(new EventCreated($event, $event->room_id));
        }
    }

    public function redirectToCalendar(Event $event): \Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();

        $startOfWeek = Carbon::parse($event->start_time)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::parse($event->end_time)->endOfWeek(Carbon::SUNDAY);

        $user->userFilters()->calendarFilter()->first()->update([
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'event_type_ids' => null,
            'room_ids' => null,
            'area_ids' => null,
            'room_attribute_ids' => null,
            'room_category_ids' => null,
            'event_property_ids' => null,
            'craft_ids' => null,
        ]);

        return redirect()->route('events', [
            'highlightEventId' => $event->id,
        ]);
    }
}

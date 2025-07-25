<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventVerificationRequest;
use App\Http\Requests\UpdateEventVerificationRequest;
use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Events\EventUpdated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventVerification;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventVerificationController extends Controller
{

    public function __construct(
        private readonly EventVerificationService $eventVerificationService,
        private readonly AuthManager $authManager,
        private readonly EventService $eventService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $myRequestPaginate = request()?->integer('myRequestsPerPage', 5);
        /** @var User $user */
        $user = $this->authManager->user();

        return Inertia::render('EventVerification/Index', [
            'myRequests' => $this->eventVerificationService->getAllRequestedByUser($user, $myRequestPaginate),
            'plannedEvents' => $this->eventVerificationService->getPlannedEvents($user),
        ]);
    }

    public function requests() {
        $requestPaginate = request()?->integer('eventVerificationsPerPage', 5);
        $filterVerificationRequest = request()?->string('filterVerificationRequest', '');
        $filterVerificationRequest = in_array($filterVerificationRequest, ['all', 'approved', 'rejected', 'pending']) ? $filterVerificationRequest : '';
        /** @var User $user */
        $user = $this->authManager->user();
        $eventVerifications = $this->eventVerificationService->getAllByUser($user, $requestPaginate, $filterVerificationRequest);

        return Inertia::render('EventVerification/Requests', [
            'eventVerifications' => $eventVerifications,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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

    /**
     * Display the specified resource.
     */
    public function show(EventVerification $eventVerification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EventVerification $eventVerification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventVerificationRequest $request, EventVerification $eventVerification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventVerification $eventVerification)
    {
        //
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
        // Get the authenticated user
        /** @var User $user */
        $user = $this->authManager->user();
        // Find all planning events for the project
        $planningEvents = Event::where('project_id', $project)
            ->where('is_planning', true)
            ->get();

        // Request verification for each planning event
        foreach ($planningEvents as $event) {
            $this->eventVerificationService->requestVerification($event, $user);
            broadcast(new EventCreated($event->fresh(), $event->room_id));
        }
    }

    /**
     * Convert all events of a project to planning events
     */
    public function convertToPlanning(Request $request, $project): void
    {
        // Find all events for the project
        $events = Event::where('project_id', $project)->get();
        // Convert each event to a planning event
        foreach ($events as $event) {
            $event->is_planning = true;
            $event->save();
            broadcast(new EventCreated($event->fresh(), $event->room_id));
        }
    }
}

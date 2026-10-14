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
use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\Event\Services\EventVerificationService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\Shift\Support\SafeBroadcast;

class EventVerificationController extends Controller
{

    public function __construct(
        private readonly EventVerificationService $eventVerificationService,
        private readonly EventSettingsService $eventSettingsService,
        private readonly AuthManager $authManager,
        private readonly ProjectTabService $projectTabService,
        private readonly UserService $userService,
    ) {
    }

    /**
     * Tab 1: Incoming requests (room booking requests + verification requests)
     */
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function index()
    {
        // "Termine immer direkt buchbar": keine Anfragen, keine Verifizierung – Seite existiert nicht mehr
        if ($this->eventSettingsService->alwaysDirectBooking()) {
            return redirect()->route('events');
        }

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
            // Anfragen zu geplanten Terminen erreichen die Raumadmins erst,
            // wenn der Termin auf einen "richtigen" Termin umgestellt wurde
            $query = Event::where('occupancy_option', true)
                ->where('is_planning', false)
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
        if ($this->eventSettingsService->alwaysDirectBooking()) {
            return redirect()->route('events');
        }

        $myRequestPaginate = request()?->integer('myRequestsPerPage', 5);
        /** @var User $user */
        $user = $this->authManager->user();

        $myRoomRequests = Event::where('user_id', $user->id)
            ->where('occupancy_option', true)
            ->with([
                'room',
                'event_type',
                'project',
                'creator',
                'eventStatus',
                'eventProperties',
                'subEvents',
                'series',
            ])
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
    public function store(Event $event): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        abort_unless($this->mayManageVerification($event, $user), 403);

        // Feste Termine haben keine Prüfung (sonst würde confirmEvent eine offene Raumanfrage fest buchen);
        // eine offene Prüfrunde wird nicht doppelt angelegt (veraltete Ansicht, Doppelklick).
        // Rückmeldung über den globalen Flash-Toast.
        if (!$event->is_planning) {
            session()->flash('error', __('Only planned events can be sent for verification.'));
            return;
        }
        if (!$this->eventVerificationService->canRequestVerification($event)) {
            return;
        }
        if ($this->eventVerificationService->lacksVerifiers($event)) {
            session()->flash('error', __('No verifiers are assigned to the event type of this event.'));
            return;
        }

        $this->eventVerificationService->requestVerification($event, $user);
        SafeBroadcast::send(new EventCreated($event->fresh(), $event->room_id));
    }

    public function approved(EventVerification $eventVerification): void
    {
        if (!$this->isOpenVerificationOfCurrentUser($eventVerification)) {
            return;
        }

        $this->eventVerificationService->approveVerification($eventVerification);
        $event = $eventVerification->event->fresh();
        SafeBroadcast::send(new EventCreated($event, $event->room_id));
    }

    public function rejected(EventVerification $eventVerification, Request $request): void
    {
        $rejectionReason = $this->validatedRejectionReason($request);
        if (!$this->isOpenVerificationOfCurrentUser($eventVerification)) {
            return;
        }

        $this->eventVerificationService->rejectVerification($eventVerification, $rejectionReason);
        $event = $eventVerification->event->fresh();
        SafeBroadcast::send(new EventCreated($event, $event->room_id));
    }

    /**
     * Prüft Recht und Zustand einer einzelnen Prüfung. Bearbeitet wird nur die eigene Prüfung (Admins: jede) –
     * die Eingangsliste zeigt Nicht-Admins auch nur ihre eigenen. Bereits beantwortete Prüfungen (zweiter
     * Klick, andere Person war schneller, alte Prüfrunde) sind ein No-op: vorher bestätigte ein erneutes
     * "Genehmigen" einer längst abgelehnten Prüfung den Termin.
     */
    private function isOpenVerificationOfCurrentUser(EventVerification $eventVerification): bool
    {
        /** @var User $user */
        $user = $this->authManager->user();
        // Termin im Papierkorb: es gibt nichts mehr zu prüfen
        abort_if($eventVerification->event === null, 404);

        $isOwnVerification = $eventVerification->verifier_type === User::class
            && (int) $eventVerification->verifier_id === $user->id;
        if (!$user->hasRole(RoleEnum::ARTWORK_ADMIN->value) && !$isOwnVerification) {
            abort(403);
        }

        return $eventVerification->status === 'pending';
    }

    private function validatedRejectionReason(Request $request): string
    {
        return (string) ($request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:65000'],
        ])['rejection_reason'] ?? '');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Event> Termine der Mehrfachauswahl; gelöschte fehlen
     */
    private function validatedEvents(Request $request): \Illuminate\Support\Collection
    {
        $eventIds = $request->validate([
            'events' => ['required', 'array'],
            'events.*' => ['integer'],
        ])['events'];

        return Event::whereIn('id', $eventIds)->get();
    }

    public function cancelVerification(Event $event): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        // Wer die Prüfung anfragen darf (store), darf sie auch zurückziehen – vorher nur Admin und Ersteller
        abort_unless($this->mayManageVerification($event, $user), 403);

        // Nur eine offene Prüfrunde lässt sich zurückziehen. Aus veralteter Ansicht machte das einen bereits
        // bestätigten Termin wieder zum geplanten und löschte den Prüfverlauf.
        if (!$event->verifications()->where('status', 'pending')->exists()) {
            return;
        }

        $this->eventVerificationService->cancelVerification($event);
        SafeBroadcast::send(new EventCreated($event, $event->room_id));
    }

    public function approvedByEvent(Event $event): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $this->eventVerificationService->approveVerificationByEvent($event, $user);
        SafeBroadcast::send(new EventCreated($event->fresh(), $event->room_id));
    }

    public function rejectByEvent(Event $event, Request $request): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $rejectionReason = $this->validatedRejectionReason($request);
        $this->eventVerificationService->rejectVerificationByEvent($event, $user, $rejectionReason);
        SafeBroadcast::send(new EventCreated($event->fresh(), $event->room_id));
    }

    // Mehrfachauswahl: gelöschte Termine (findEventById → null, vorher TypeError) werden übersprungen
    public function rejectByEvents(Request $request): void
    {
        foreach ($this->validatedEvents($request) as $event) {
            $this->rejectByEvent($event, $request);
        }
    }

    public function approvedByEvents(Request $request): void
    {
        foreach ($this->validatedEvents($request) as $event) {
            $this->approvedByEvent($event);
        }
    }

    public function requestVerification(Request $request): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        // Die Auswahl kann feste Termine ("Feste Termine anzeigen") und fremde Termine (nur Sichtrecht) enthalten:
        // überspringen statt die Anfrage mittendrin mit 403/422 abzubrechen (vorher halb verschickt)
        $skippedForeignEvents = 0;
        foreach ($this->validatedEvents($request) as $event) {
            if (!$event->is_planning) {
                continue;
            }
            if (!$this->mayManageVerification($event, $user)) {
                $skippedForeignEvents++;
                continue;
            }
            $this->store($event);
        }

        if ($skippedForeignEvents > 0) {
            session()->flash('error', trans_choice(
                '{1} One event of someone else was skipped: '
                . 'you may only request verification for your own planned events.|'
                . '[2,*] :count events of others were skipped: '
                . 'you may only request verification for your own planned events.',
                $skippedForeignEvents,
                ['count' => $skippedForeignEvents]
            ));
        }
    }

    /**
     * Prüfung anfragen/zurückziehen: Admin, "Geplante Termine bearbeiten" oder die anlegende Person.
     */
    private function mayManageVerification(Event $event, User $user): bool
    {
        return $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
            || $user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value)
            || $event->creator?->id === $user->id;
    }

    /**
     * Request verification for all planning events in a project
     */
    public function requestVerificationForProject(Request $request, $project): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (
            !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value) &&
            !$user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value)
        ) {
            abort(403);
        }

        $planningEvents = Event::where('project_id', $project)
            ->where('is_planning', true)
            ->get()
            // Termine mit offener Prüfrunde nicht doppelt anfragen, Terminarten ohne Prüfende überspringen
            ->filter(fn (Event $event) => $this->eventVerificationService->canRequestVerification($event)
                && !$this->eventVerificationService->lacksVerifiers($event));

        foreach ($planningEvents as $event) {
            $this->eventVerificationService->requestVerification($event, $user);
        }

        $refreshedEvents = Event::whereIn('id', $planningEvents->pluck('id'))->get();
        foreach ($refreshedEvents as $event) {
            SafeBroadcast::send(new EventCreated($event, $event->room_id));
        }
    }

    /**
     * Convert all events of a project to planning events
     */
    public function convertToPlanning(Request $request, $project): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (
            !$user->hasRole(RoleEnum::ARTWORK_ADMIN->value) &&
            !$user->can(PermissionEnum::CAN_EDIT_PLANNING_CALENDAR->value)
        ) {
            abort(403);
        }

        $events = Event::where('project_id', $project)->get();
        $eventIds = $events->pluck('id');

        // Je Termin wie die Einzel-Umstellung: Änderungsverlauf, offene Raumanfrage zurücknehmen
        // (vorher Massen-Update nur von is_planning)
        foreach ($events as $event) {
            $this->eventVerificationService->convertToPlanning($event);
        }

        $refreshedEvents = Event::whereIn('id', $eventIds)->get();
        foreach ($refreshedEvents as $event) {
            SafeBroadcast::send(new EventCreated($event, $event->room_id));
        }
    }

    public function redirectToCalendar(Event $event): \Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = $this->authManager->user();

        $startOfWeek = Carbon::parse($event->start_time)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::parse($event->end_time)->endOfWeek(Carbon::SUNDAY);

        // Geplante Termine (Verifizierungsanfragen) liegen im Planungskalender; im Kalender erscheinen sie nur
        // mit "Geplante Termine anzeigen" – der Sprung landete sonst in einer Woche ohne den Termin
        $openPlanningCalendar = $event->is_planning && $user->can('viewPlanning', Event::class);

        $this->userService->focusCalendarOnPeriod($user, $startOfWeek, $endOfWeek, [
            'event_type_ids' => null,
            'room_ids' => null,
            'area_ids' => null,
            'room_attribute_ids' => null,
            'room_category_ids' => null,
            'event_property_ids' => null,
            'craft_ids' => null,
            // Blendet sonst den Zieltermin aus (und alle Termine ohne Projekt)
            'project_state_ids' => null,
        ], $openPlanningCalendar);

        return redirect()->route($openPlanningCalendar ? 'planning-event-calendar.index' : 'events', [
            'highlightEventId' => $event->id,
        ]);
    }
}

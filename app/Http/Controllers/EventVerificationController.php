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
        private readonly AuthManager $authManager
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requestPaginate = request()?->integer('eventVerificationsPerPage', 5);
        $myRequestPaginate = request()?->integer('myRequestsPerPage', 5);
        $filterVerificationRequest = request()?->string('filterVerificationRequest', '');
        $filterVerificationRequest = in_array($filterVerificationRequest, ['all', 'approved', 'rejected', 'pending']) ? $filterVerificationRequest : '';
        /** @var User $user */
        $user = $this->authManager->user();
        $eventVerifications = $this->eventVerificationService->getAllByUser($user, $requestPaginate, $filterVerificationRequest);

        return Inertia::render('EventVerification/Index', [
            'eventVerifications' => $eventVerifications,
            'myRequests' => $this->eventVerificationService->getAllByRequester($user, $myRequestPaginate),
            'counts' => $this->eventVerificationService->getCountsByUser($user),
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
        $this->eventVerificationService->rejectVerification($eventVerification, $request->get('rejection_reason'));
        $event = $eventVerification->event;
        broadcast(new EventCreated($event, $event->room_id));
    }
}

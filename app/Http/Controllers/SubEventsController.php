<?php

namespace App\Http\Controllers;

use App\Enums\NotificationConstEnum;
use App\Models\Event;
use App\Models\SubEvents;
use App\Support\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubEventsController extends Controller
{
    protected ?NotificationService $notificationService = null;
    protected ?\stdClass $notificationData = null;


    public function __construct()
    {
        $this->notificationService = new NotificationService();
        $this->notificationData = new \stdClass();
        $this->notificationData->event = new \stdClass();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function store(Request $request): void
    {
        SubEvents::create($request->only([
            'event_id',
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud',
        ]));

        // Send Notification to Room Admins
        $event = Event::find($request->event_id);
        $room = $event->room()->first();
        $roomAdmins = $room->users()->wherePivot('is_admin', true)->get();
        foreach ($roomAdmins as $roomAdmin){
            $notificationTitle = 'Lauter Termin im Nebenraum';
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $this->notificationService->createNotification($roomAdmin, $notificationTitle, [], NotificationConstEnum::NOTIFICATION_UPSERT_ROOM_REQUEST, 'red', [], false, '', null, $broadcastMessage, null, $event->id);
            //$this->notificationService->create($user, $this->notificationData, $broadcastMessage);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SubEvents  $subEvents
     * @return \Illuminate\Http\Response
     */
    public function show(SubEvents $subEvents)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SubEvents  $subEvents
     * @return \Illuminate\Http\Response
     */
    public function edit(SubEvents $subEvents)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SubEvents  $subEvents
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SubEvents $subEvents)
    {
        $subEvents->update($request->only([
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud'
        ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubEvents  $subEvents
     */
    public function destroy(SubEvents $subEvents)
    {
        $subEvents->delete();
    }
}

<?php

namespace App\Http\Controllers;

use App\Settings\EventSettings;
use Artwork\Modules\Event\Http\Requests\CreateEventStatusRequest;
use Artwork\Modules\Event\Http\Requests\UpdateEventStatusRequest;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Event\Services\EventStatusService;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventStatusController extends Controller
{
    public function __construct(
        private readonly EventStatusService $eventStatusService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('Settings/EventStatus/Index', [
            'eventStatuses' => EventStatus::orderBy('order')->get(),
            'enable_status' => app(EventSettings::class)->enable_status,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateEventStatusRequest $request): void
    {
        $this->eventStatusService->create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(EventStatus $eventStatus): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EventStatus $eventStatus): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventStatusRequest $request, EventStatus $eventStatus): void
    {
        $this->eventStatusService->update($eventStatus, $request->validated());
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateSettings(Request $request): void
    {
        $this->eventStatusService->updateSettings($request);
    }

    public function reorder(Request $request): void
    {
        foreach ($request->input('eventStatuses') as $eventStatus) {
            EventStatus::find($eventStatus['id'])->update(['order' => $eventStatus['order']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventStatus $eventStatus): void
    {
        $eventsWithStatus = Event::where('event_status_id', $eventStatus->id)->get();
        $eventsWithStatus->each(function ($event): void {
            $event->update(['event_status_id' => 1]);
        });
        $this->eventStatusService->delete($eventStatus);
    }
}

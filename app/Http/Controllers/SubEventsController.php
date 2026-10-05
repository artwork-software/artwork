<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SubEvent;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

class SubEventsController extends Controller
{
    public function store(Request $request): bool
    {
        // Untertermine folgen der Bearbeitungsregel des Haupttermins (EventPolicy::update)
        $this->authorize('update', Event::findOrFail($request->input('event_id')));

        $subevent = SubEvent::create($request->only([
            'event_id',
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud',
            'allDay'
        ]));

        // add Properties to SubEvent
        $subevent->eventProperties()->sync($request->get('eventProperties'));


        // Vorher ging hier bei JEDEM Untertermin „Lauter Termin im Nebenraum“ an die Admins des eigenen
        // Raums (unabhängig von Lautstärke/Nebenraum) und brach ohne Raum mit 500 ab – entfernt.
        $event = Event::find($request->event_id);

        broadcast(new EventCreated(
            $event,
            $event->room_id
        ));
        return true;
    }

    public function update(Request $request, SubEvent $subEvents): bool
    {
        $this->authorize('update', $subEvents->event()->firstOrFail());

        $subEvents->update($request->only([
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud',
            'allDay'
        ]));

        // add Properties to SubEvent
        $subEvents->eventProperties()->sync($request->get('eventProperties'));

        $event = $subEvents->event;

        broadcast(new EventCreated($event, $event->room_id));

        return true;
    }

    public function destroy(SubEvent $subEvents): void
    {
        $event = $subEvents->event;
        abort_unless($event !== null, 404);
        $this->authorize('update', $event);
        broadcast(new EventCreated($event, $event->room_id));

        $subEvents->forceDelete();
    }
}

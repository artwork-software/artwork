<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Events\EventCreated;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SubEvent;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Artwork\Modules\Shift\Support\SafeBroadcast;

class SubEventsController extends Controller
{
    public function store(Request $request): bool
    {
        // Untertermine folgen der Bearbeitungsregel des Haupttermins (EventPolicy::update)
        $this->authorize('update', Event::findOrFail($request->input('event_id')));

        // Ersteller:in ist immer die angemeldete Person – ein mitgeschicktes user_id wird ignoriert
        $subevent = SubEvent::create([
            ...$request->only([
                'event_id',
                'eventName',
                'description',
                'start_time',
                'end_time',
                'event_type_id',
                'audience',
                'is_loud',
                'allDay'
            ]),
            'user_id' => Auth::id(),
        ]);

        // add Properties to SubEvent
        $subevent->eventProperties()->sync($request->get('eventProperties'));


        // Vorher ging hier bei JEDEM Untertermin „Lauter Termin im Nebenraum“ an die Admins des eigenen
        // Raums (unabhängig von Lautstärke/Nebenraum) und brach ohne Raum mit 500 ab – entfernt.
        $event = Event::find($request->event_id);

        SafeBroadcast::send(new EventCreated(
            $event,
            $event->room_id
        ));
        return true;
    }

    public function update(Request $request, SubEvent $subEvents): bool
    {
        $this->authorize('update', $subEvents->event()->firstOrFail());

        // user_id bleibt beim Bearbeiten unverändert (Ersteller:in des Untertermins)
        $subEvents->update($request->only([
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'audience',
            'is_loud',
            'allDay'
        ]));

        // add Properties to SubEvent
        $subEvents->eventProperties()->sync($request->get('eventProperties'));

        $event = $subEvents->event;

        SafeBroadcast::send(new EventCreated($event, $event->room_id));

        return true;
    }

    public function destroy(SubEvent $subEvents): void
    {
        $event = $subEvents->event;
        abort_unless($event !== null, 404);
        $this->authorize('update', $event);

        $subEvents->forceDelete();

        // Erst nach dem Löschen senden – sonst enthält die Nutzlast den gelöschten Untertermin noch
        SafeBroadcast::send(new EventCreated($event, $event->room_id));
    }
}

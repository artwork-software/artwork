<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypeResource;
use App\Models\EventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;

class EventTypeController extends Controller
{
    public function index(): Response|ResponseFactory
    {
        return inertia('Settings/EventSettings', [
            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EventType::create([
            'name' => $request->get('name'),
            'hex_code' => $request->get('hex_code', '#EC7A3D'),
            'project_mandatory' => $request->get('project_mandatory'),
            'individual_name' => $request->get('individual_name'),
            'abbreviation' => $request->get('abbreviation'),
        ]);

        return Redirect::back();
    }

    public function show(EventType $eventType): Response|ResponseFactory
    {
        return inertia('Events/EventType', [
            'event_type' => new EventTypeResource($eventType),
        ]);
    }

    public function update(Request $request, EventType $eventType): RedirectResponse
    {
        $eventType->update($request->all());

        return Redirect::route('event_types.management');
    }

    public function destroy(EventType $eventType)
    {
        if ($eventType->name !== 'undefiniert') {
            $events = $eventType->events()->get();

            foreach ($events as $event) {
                $event->update(['event_type_id' => 1]);
            }
            $eventType->delete();

            return Redirect::route('event_types.management');
        } else {
            return response()->json(['error' => 'This EventType cant be deleted.'], 403);
        }
    }

    public function updateRelevant(Request $request, EventType $eventType): void
    {
        $eventType->update(['relevant_for_shift' => $request->relevant_for_shift]);
    }
}

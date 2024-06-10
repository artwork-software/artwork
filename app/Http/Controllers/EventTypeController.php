<?php

namespace App\Http\Controllers;

use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
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

    public function store(Request $request, EventTypeService $eventTypeService): RedirectResponse
    {
        $eventTypeService->save($this->setProperties(new EventType(), $request));
        return Redirect::back();
    }

    public function show(EventType $eventType): Response|ResponseFactory
    {
        return inertia('Events/EventType', [
            'event_type' => new EventTypeResource($eventType),
        ]);
    }

    public function update(Request $request, EventType $eventType, EventTypeService $eventTypeService): RedirectResponse
    {
        $eventTypeService->save($this->setProperties($eventType, $request));

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

    private function setProperties(EventType $eventType, Request $request): EventType
    {
        $eventType->name = $request->get('name', $eventType->name);
        $eventType->hex_code = $request->get('hex_code') ?? $eventType->hex_code ?: '#EC7A3D';
        $eventType->project_mandatory = $request->get('project_mandatory', $eventType->project_mandatory);
        $eventType->individual_name = $request->get('individual_name', $eventType->individual_name);
        $eventType->abbreviation = $request->get('abbreviation', $eventType->abbreviation);

        return $eventType;
    }
}

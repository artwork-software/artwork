<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\EventType\Services\EventTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        //dd($request->all());
        $eventType = $eventTypeService->save($this->setProperties($eventType, $request));

        $eventType->verifiers()->detach();
        $eventType->verifiers()->attach($request->collect('users', [])->pluck('id'));

        return Redirect::route('event_types.management');
    }

    public function destroy(EventType $eventType)
    {
        if ($eventType->getAttribute('id') === 1) {
            return Redirect::back();
        }

        try {
            // Get all events associated with this event type
            $eventType->events()->update(['event_type_id' => 1]);
            $eventType->verifiers()->detach();
            $eventType->subEvents()->update(['event_type_id' => 1]);
            // Delete the event type after all events have been reassigned
            $eventType->delete();
            return Redirect::route('event_types.management');
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function updateRelevant(Request $request, EventType $eventType): void
    {
        $eventType->update(['relevant_for_shift' => $request->relevant_for_shift]);
    }

    public function updateRelevantForInventory(Request $request, EventType $eventType): void
    {
        $eventType->update(['relevant_for_inventory' => $request->relevant_for_inventory]);
    }

    private function setProperties(EventType $eventType, Request $request): EventType
    {
        $eventType->name = $request->get('name', $eventType->name);
        $eventType->hex_code = $request->get('hex_code') ?? $eventType->hex_code ?: '#EC7A3D';
        $eventType->project_mandatory = $request->get('project_mandatory', $eventType->project_mandatory);
        $eventType->individual_name = $request->get('individual_name', $eventType->individual_name);
        $eventType->abbreviation = $request->get('abbreviation', $eventType->abbreviation);
        $eventType->abbreviation = $request->get('abbreviation', $eventType->abbreviation);
        $eventType->relevant_for_project_period = $request->get('relevant_for_project_period', $eventType->relevant_for_project_period);
        $eventType->verification_mode = $request->get('verification_mode', $eventType->verification_mode);
        $eventType->specific_verifier_id = $request->get('specific_verifier_id', $eventType->specific_verifier_id);
        return $eventType;
    }
}

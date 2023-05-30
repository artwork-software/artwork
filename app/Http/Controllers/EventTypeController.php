<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypeResource;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EventTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function index()
    {
        return inertia('Settings/EventSettings', [
            'event_types' => EventTypeResource::collection(EventType::all())->resolve(),
        ]);
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
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $event_type = EventType::create([
            'name' => $request->name,
            'svg_name' => $request->svg_name,
            'project_mandatory' => $request->project_mandatory,
            'individual_name' => $request->individual_name,
            'abbreviation' => $request->abbreviation
        ]);

        return Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EventType  $eventType
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(EventType $eventType)
    {
        return inertia('Events/EventType', [
            'event_type' => new EventTypeResource($eventType),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EventType  $eventType
     * @return \Illuminate\Http\Response
     */
    public function edit(EventType $eventType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EventType  $eventType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, EventType $eventType)
    {
        $eventType->update($request->only(
            'name',
            'svg_name',
            'project_mandatory',
            'individual_name',
            'abbreviation'
        ));

        return Redirect::route('event_types.management')->with('success', 'EventType updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EventType  $eventType
     */
    public function destroy(EventType $eventType)
    {
        if ($eventType->name !== 'undefiniert') {

            $events = $eventType->events()->get();

            foreach ($events as $event){
                $event->update(['event_type_id' => 1]);
            }
            $eventType->delete();

            return Redirect::route('event_types.management')->with('success', 'EventType deleted');
        } else {
            return response()->json(['error' => 'This EventType cant be deleted.'], 403);
        }
    }

    public function updateRelevant(Request $request, EventType $eventType) {
        $eventType->update(['relevant_for_shift' => $request->relevant_for_shift]);
    }
}

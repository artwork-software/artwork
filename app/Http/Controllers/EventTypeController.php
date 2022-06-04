<?php

namespace App\Http\Controllers;

use App\Models\EventType;
use Carbon\Carbon;
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
        return inertia('Events/EventSettings', [
            'event_types' => EventType::paginate(10)->through(fn($event_type) => [
                'id' => $event_type->id,
                'name' => $event_type->name,
                'svg_name' => $event_type->svg_name,
                'project_mandatory' => $event_type->project_mandatory,
                'individual_name' => $event_type->individual_name,
            ]),
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $event_type = EventType::create([
            'name' => $request->name,
            'svg_name' => $request->svg_name,
            'project_mandatory' => $request->project_mandatory,
            'individual_name' => $request->individual_name,
        ]);

        return Redirect::route('event_types.show', $event_type)->with('success', 'EventType created.');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\EventType $eventType
     * @return \Inertia\Response|\Inertia\ResponseFactory
     */
    public function show(EventType $eventType)
    {
        return inertia('Events/EventType', [
            'event_type' => [
                'id' => $eventType->id,
                'name' => $eventType->name,
                'svg_name' => $eventType->svg_name,
                'project_mandatory' => $eventType->project_mandatory,
                'individual_name' => $eventType->individual_name,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\EventType $eventType
     * @return \Illuminate\Http\Response
     */
    public function edit(EventType $eventType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\EventType $eventType
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, EventType $eventType)
    {
        $eventType->update($request->only(
            'name',
            'svg_name',
            'project_mandatory',
            'individual_name',
        ));

        return Redirect::route('event_types.management')->with('success', 'EventType updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\EventType $eventType
     */
    public function destroy(EventType $eventType)
    {
        if($eventType->name !== 'undefiniert') {
            $eventType->delete();
            return Redirect::route('$event_types.management')->with('success', 'EventType deleted');
        }
        else {
            return response()->json(['error' => 'This EventType cant be deleted.'], 403);
        }
    }
}

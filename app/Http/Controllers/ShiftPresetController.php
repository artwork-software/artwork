<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Shift;
use App\Models\ShiftPreset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftPresetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        $shiftPresets = ShiftPreset::with(['event_type', 'shifts', 'timeLine'])->get();

        foreach ($shiftPresets as $shiftPreset) {
            $timeline = $shiftPreset->timeLine()->get()->toArray();

            usort($timeline, function ($a, $b) {
                if ($a['start'] === null && $b['start'] === null) {
                    return 0;
                } elseif ($a['start'] === null) {
                    return 1; // $a should come later in the array
                } elseif ($b['start'] === null) {
                    return -1; // $b should come later in the array
                }

                // Compare the 'start' values for ascending order
                return strtotime($a['start']) - strtotime($b['start']);
            });

            $shiftPreset->setRelation('timeLine', collect($timeline));
        }


        return Inertia::render('Shifts/ShiftPresets', [
            'shiftPresets' => $shiftPresets,
            'crafts' => Craft::all(),
            'event_types' => EventType::all()
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
     * @return \Illuminate\Http\Response
     */
    public function store(Event $event, Request $request)
    {
        $shifts = $event->shifts()->get();
        $timeLines = $event->timeline()->get();

        $shiftPreset = ShiftPreset::create([
            'name' => $request->name,
            'event_type_id' => $request->event_type_id
        ]);

        foreach ($shifts as $shift) {
            $shiftPreset->shifts()->create([
                'start' => $shift->start,
                'end' => $shift->end,
                'break_minutes' => $shift->break_minutes,
                'craft_id' => $shift->craft_id,
                'number_employees' => $shift->number_employees,
                'number_masters' => $shift->number_masters,
                'description' => $shift->description,
                'is_committed' => $shift->is_committed
            ]);
        }

        foreach ($timeLines as $timeLine) {
            $shiftPreset->timeLine()->create([
                'start' => $timeLine->start,
                'end' => $timeLine->end,
                'description' => $timeLine->description,
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ShiftPreset  $shiftPreset
     * @return \Illuminate\Http\Response
     */
    public function show(ShiftPreset $shiftPreset)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ShiftPreset  $shiftPreset
     * @return \Illuminate\Http\Response
     */
    public function edit(ShiftPreset $shiftPreset)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ShiftPreset  $shiftPreset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ShiftPreset $shiftPreset)
    {
        $shiftPreset->update($request->only(['name', 'event_type_id']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ShiftPreset  $shiftPreset
     * @return \Illuminate\Http\Response
     */
    public function destroy(ShiftPreset $shiftPreset)
    {
        $shiftPreset->delete();
    }

    public function duplicate(ShiftPreset $shiftPreset){
        $shifts = $shiftPreset->shifts()->get();
        $timeLines = $shiftPreset->timeline()->get();

        $shiftPreset = ShiftPreset::create([
            'name' => $shiftPreset->name . ' (Kopie)',
            'event_type_id' => $shiftPreset->event_type_id
        ]);

        foreach ($shifts as $shift) {
            $shiftPreset->shifts()->create([
                'start' => $shift->start,
                'end' => $shift->end,
                'break_minutes' => $shift->break_minutes,
                'craft_id' => $shift->craft_id,
                'number_employees' => $shift->number_employees,
                'number_masters' => $shift->number_masters,
                'description' => $shift->description,
                'is_committed' => $shift->is_committed
            ]);
        }

        foreach ($timeLines as $timeLine) {
            $shiftPreset->timeLine()->create([
                'start' => $timeLine->start,
                'end' => $timeLine->end,
                'description' => $timeLine->description,
            ]);
        }
    }

    public function addNewShift(Request $request, ShiftPreset $shiftPreset){
        $shiftPreset->shifts()->create([
            'start' => $request->start,
            'end' => $request->end,
            'break_minutes' => $request->break_minutes,
            'craft_id' => $request->craft_id,
            'number_employees' => $request->number_employees,
            'number_masters' => $request->number_masters,
            'description' => $request->description,
            'is_committed' => $request->is_committed
        ]);
    }

    public function storeEmpty(Request $request)
    {
        ShiftPreset::create([
            'name' => $request->name,
            'event_type_id' => $request->event_type_id
        ]);

    }

    public function search(Request $request){
        $query = $request->input('query');

        // Assuming you also have an 'eventTypeId' parameter in your request
        $eventTypeId = $request->input('eventTypeId');

        // Perform a full-text search using Laravel Scout
        $shiftPresets = ShiftPreset::search($query);

        $returnArray = [];

        foreach ($shiftPresets->get() as $shiftPreset) {
            if($shiftPreset->event_type_id == $eventTypeId){
                array_push($returnArray, $shiftPreset);
            }
        }

        return $returnArray;
    }

    public function import(Request $request, Event $event, ShiftPreset $shiftPreset)
    {
        if($request->all === true){
            $project = $event->project()->first();

            $eventsByProject = $project->events()->where('event_type_id', $shiftPreset->event_type_id)->get();

            foreach ($eventsByProject as $eventByProject){
                $eventByProject->shifts()->delete();
                $eventByProject->timeline()->delete();

                $shifts = $shiftPreset->shifts()->get();
                $timeLines = $shiftPreset->timeline()->get();

                foreach ($shifts as $shift){
                    $eventByProject->shifts()->create([
                        'start' => $shift->start,
                        'end' => $shift->end,
                        'break_minutes' => $shift->break_minutes,
                        'craft_id' => $shift->craft_id,
                        'number_employees' => $shift->number_employees,
                        'number_masters' => $shift->number_masters,
                        'description' => $shift->description,
                        'is_committed' => false
                    ]);
                }

                foreach ($timeLines as $timeLine){
                    $eventByProject->timeline()->create([
                        'start' => $timeLine->start,
                        'end' => $timeLine->end,
                        'description' => $timeLine->description,
                    ]);
                }
            }

        } else {
            $event->shifts()->delete();
            $event->timeline()->delete();

            $shifts = $shiftPreset->shifts()->get();
            $timeLines = $shiftPreset->timeline()->get();

            foreach ($shifts as $shift){
                $event->shifts()->create([
                    'start' => $shift->start,
                    'end' => $shift->end,
                    'break_minutes' => $shift->break_minutes,
                    'craft_id' => $shift->craft_id,
                    'number_employees' => $shift->number_employees,
                    'number_masters' => $shift->number_masters,
                    'description' => $shift->description,
                    'is_committed' => false
                ]);
            }

            foreach ($timeLines as $timeLine){
                $event->timeline()->create([
                    'start' => $timeLine->start,
                    'end' => $timeLine->end,
                    'description' => $timeLine->description,
                ]);
            }
        }
    }
}

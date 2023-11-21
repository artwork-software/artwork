<?php

namespace App\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetTimeLine;
use Artwork\Modules\Shift\Models\ShiftPreset;
use Illuminate\Http\Request;

class PresetTimeLineController extends Controller
{
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, ShiftPreset $shiftPreset)
    {
        $shiftPreset->timeLine()->create();
    }

    /**
     * Display the specified resource.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetTimeLine  $presetTimeLine
     * @return \Illuminate\Http\Response
     */
    public function show(PresetTimeLine $presetTimeLine)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetTimeLine  $presetTimeLine
     * @return \Illuminate\Http\Response
     */
    public function edit(PresetTimeLine $presetTimeLine)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Artwork\Modules\Shift\Models\PresetTimeLine  $presetTimeLine
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        foreach ($request->timelines as $timeline){
            $findTimeLine = PresetTimeLine::find($timeline['id']);
            $findTimeLine->update([
                'start' => $timeline['start'],
                'end' => $timeline['end'],
                'description' => $timeline['description']
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetTimeLine  $presetTimeLine
     * @return void
     */
    public function destroy(PresetTimeLine $presetTimeLine) : void
    {
        $presetTimeLine->delete();
    }
}

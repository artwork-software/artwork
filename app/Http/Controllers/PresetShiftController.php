<?php

namespace App\Http\Controllers;

use Artwork\Modules\Shift\Models\PresetShift;
use Illuminate\Http\Request;

class PresetShiftController extends Controller
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetShift  $presetShift
     * @return \Illuminate\Http\Response
     */
    public function show(PresetShift $presetShift)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetShift  $presetShift
     * @return \Illuminate\Http\Response
     */
    public function edit(PresetShift $presetShift)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Artwork\Modules\Shift\Models\PresetShift  $presetShift
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PresetShift $presetShift)
    {
        $presetShift->update($request->only(['start', 'end', 'break_minutes', 'craft_id', 'number_employees', 'number_masters', 'description']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\Shift\Models\PresetShift  $presetShift
     * @return \Illuminate\Http\Response
     */
    public function destroy(PresetShift $presetShift)
    {
        $presetShift->delete();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
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
    public function store(Request $request, Event $event)
    {
        $event->shifts()->create($request->only([
            'start',
            'end',
            'break_minutes',
            'craft_id',
            'number_employees',
            'number_masters',
            'description',
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\Response
     */
    public function show(Shift $shift)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\Response
     */
    public function edit(Shift $shift)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Shift $shift)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Shift  $shift
     * @return \Illuminate\Http\Response
     */
    public function destroy(Shift $shift)
    {
        //
    }

    public function addShiftUser(Request $request, Shift $shift){
        $shift->users()->attach($request->user_id);
    }

    public function addShiftMaster(Request $request, Shift $shift): void
    {
        $shift->users()->attach($request->user_id, ['is_master' => true]);
    }


    public function addShiftFreelancer(Request $request, Shift $shift): void
    {
        $shift->freelancer()->attach($request->freelancer_id);
    }

    public function addShiftFreelancerMaster(Request $request, Shift $shift): void
    {
        $shift->freelancer()->attach($request->freelancer_id, ['is_master' => true]);
    }

    public function addShiftProvider(Request $request, Shift $shift): void
    {
        $shift->service_provider()->attach($request->service_provider_id);
    }

    public function addShiftProviderMaster(Request $request, Shift $shift): void
    {
        $shift->service_provider()->attach($request->service_provider_id, ['is_master' => true]);
    }

    public function removeUser(Request $request, Shift $shift): void
    {
        $shift->users()->detach($request->user_id);
    }

    public function removeFreelancer(Request $request, Shift $shift): void
    {
        $shift->freelancer()->detach($request->freelancer_id);
    }

    public function removeProvider(Request $request, Shift $shift): void
    {
        $shift->service_provider()->detach($request->service_provider_id);
    }
}

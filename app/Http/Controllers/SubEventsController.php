<?php

namespace App\Http\Controllers;

use App\Models\SubEvents;
use Illuminate\Http\Request;

class SubEventsController extends Controller
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
     * @return void
     */
    public function store(Request $request): void
    {
        SubEvents::create($request->only([
            'event_id',
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud'
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SubEvents  $subEvents
     * @return \Illuminate\Http\Response
     */
    public function show(SubEvents $subEvents)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SubEvents  $subEvents
     * @return \Illuminate\Http\Response
     */
    public function edit(SubEvents $subEvents)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SubEvents  $subEvents
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SubEvents $subEvents)
    {
        $subEvents->update($request->only([
            'eventName',
            'description',
            'start_time',
            'end_time',
            'event_type_id',
            'user_id',
            'audience',
            'is_loud'
        ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubEvents  $subEvents
     */
    public function destroy(SubEvents $subEvents)
    {
        $subEvents->delete();
    }
}

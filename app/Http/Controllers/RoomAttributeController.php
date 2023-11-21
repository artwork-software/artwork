<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Artwork\Modules\Room\Models\RoomAttribute;

class RoomAttributeController extends Controller
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
        RoomAttribute::create([
            'name' => $request->get('name')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \RoomAttribute  $roomAttribute
     * @return \Illuminate\Http\Response
     */
    public function show(RoomAttribute $roomAttribute)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \RoomAttribute  $roomAttribute
     * @return \Illuminate\Http\Response
     */
    public function edit(RoomAttribute $roomAttribute)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \RoomAttribute  $roomAttribute
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RoomAttribute $roomAttribute)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \RoomAttribute  $roomAttribute
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RoomAttribute $roomAttribute)
    {
        $roomAttribute->delete();

        return Redirect::route('areas.management')->with('success', 'Roomattribute deleted');
    }
}

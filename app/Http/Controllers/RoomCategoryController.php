<?php

namespace App\Http\Controllers;

use App\Models\RoomCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RoomCategoryController extends Controller
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
        RoomCategory::create([
            'name' => $request->get('name')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RoomCategory  $roomCategory
     * @return \Illuminate\Http\Response
     */
    public function show(RoomCategory $roomCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RoomCategory  $roomCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(RoomCategory $roomCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RoomCategory  $roomCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RoomCategory $roomCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RoomCategory  $roomCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(RoomCategory $roomCategory)
    {
        $roomCategory->delete();
        return Redirect::route('areas.management')->with('success', 'Roomcategory deleted');

    }
}

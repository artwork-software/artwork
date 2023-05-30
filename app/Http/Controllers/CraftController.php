<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use Illuminate\Http\Request;

class CraftController extends Controller
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
        $craft = Craft::create($request->only(['name', 'abbreviation']));
        if(!$request->assignable_by_all){
            $craft->update(['assignable_by_all' => false]);
            $craft->users()->sync($request->users);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Craft  $craft
     * @return \Illuminate\Http\Response
     */
    public function show(Craft $craft)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Craft  $craft
     * @return \Illuminate\Http\Response
     */
    public function edit(Craft $craft)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Craft  $craft
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Craft $craft)
    {
        $craft->update($request->only(['name', 'abbreviation']));
        if(!$request->assignable_by_all){
            $craft->update(['assignable_by_all' => false]);
            $craft->users()->sync($request->users);
        } else {
            $craft->update(['assignable_by_all' => true]);
            $craft->users()->detach();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Craft  $craft
     * @return \Illuminate\Http\Response
     */
    public function destroy(Craft $craft)
    {
        //
    }
}

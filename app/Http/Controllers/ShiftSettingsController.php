<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Models\EventType;
use App\Models\User;
use Artwork\Modules\Craft\Models\Craft;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('Settings/ShiftSettings', [
            'crafts' => Craft::all(),
            'eventTypes' => EventType::all(),
            'usersWithPermission' => User::permission(PermissionNameEnum::SHIFT_PLANNER->value)->get(),
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

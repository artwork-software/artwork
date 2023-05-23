<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserVacations;
use Illuminate\Http\Request;

class UserVacationsController extends Controller
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
     * @param Request $request
     * @param User $user
     */
    public function store(Request $request, User $user): void
    {
        $user->vacations()->create($request->only(['from', 'until']));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserVacations  $userVacations
     * @return \Illuminate\Http\Response
     */
    public function show(UserVacations $userVacations)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserVacations  $userVacations
     * @return \Illuminate\Http\Response
     */
    public function edit(UserVacations $userVacations)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UserVacations  $userVacations
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserVacations $userVacations)
    {
        $userVacations->update($request->only(['from', 'until']));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserVacations  $userVacations
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserVacations $userVacations)
    {
        $userVacations->delete();
    }
}

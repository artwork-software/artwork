<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserVacations;
use App\Support\Services\NewHistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserVacationsController extends Controller
{

    protected ?NewHistoryService $history = null;
    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\UserVacations');
    }
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
        $vacation = $user->vacations()->create($request->only(['from', 'until']));
        $schedule = new SchedulingController();
        $schedule->create($user->id, 'VACATION_CHANGES', 'USER_VACATIONS', $user->id);
        $this->history->setHistoryText('Verfügbarkeit hinzugefügt');
        $this->history->setModelId($vacation->id);
        $this->history->setType('vacation');
        $this->history->create();
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
        $oldFrom = $userVacations->from;
        $oldUntil = $userVacations->until;
        $userVacations->update($request->only(['from', 'until']));

        $newFrom = $userVacations->from;
        $newUntil = $userVacations->until;
        if($oldFrom !== $newFrom){
            $this->history->setHistoryText('Verfügbarkeit geändert von ' . Carbon::parse($oldFrom)->format('d.m.Y') . ' zu ' . Carbon::parse($newFrom)->format('d.m.Y'));
            $this->history->setModelId($userVacations->id);
            $this->history->setType('vacation');
            $this->history->create();
            //$this->history->createHistory($userVacations->id, 'User changed vacation from ' . $oldFrom . ' to ' . $newFrom, 'vacation');
        }

        if($oldUntil !== $newUntil){
            $this->history->setHistoryText('Verfügbarkeit geändert bis: ' . Carbon::parse($oldUntil)->format('d.m.Y') . ' zu ' . Carbon::parse($newUntil)->format('d.m.Y'));
            $this->history->setModelId($userVacations->id);
            $this->history->setType('vacation');
            $this->history->create();
            //$this->history->createHistory($userVacations->id, 'User changed vacation from ' . $oldUntil . ' to ' . $newUntil, 'vacation');
        }
        //$this->history->createHistory($userVacations->id, 'User changed vacation from ' . $oldFrom . ' to ' . $newFrom, 'vacation');
        $schedule = new SchedulingController();
        $schedule->create($userVacations->user()->first()->id, 'VACATION_CHANGES', 'USER_VACATIONS', $userVacations->user()->first()->id);

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

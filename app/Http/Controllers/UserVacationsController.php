<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserVacations;
use App\Support\Services\NewHistoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserVacationsController extends Controller
{
    protected ?NewHistoryService $history = null;
    public function __construct()
    {
        $this->history = new NewHistoryService('App\Models\UserVacations');
    }

    public function index(): void
    {
    }

    public function create(): void
    {
    }

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

    public function show(): void
    {
    }

    public function edit(): void
    {
    }

    public function checkVacation(Request $request, User $user): void
    {
        if ($request->checked) {
            $vacations = $user
                ->vacations()
                ->where('from', '<=', Carbon::parse($request->day)->format('Y-m-d'))
                ->where('until', '>=', Carbon::parse($request->day)->format('Y-m-d'))
                ->get();
            foreach ($vacations as $vacation) {
                $vacation->delete();
            }
        } else {
            $vacations = $user
                ->vacations()
                ->where('from', '<=', Carbon::parse($request->day)->format('Y-m-d'))
                ->where('until', '>=', Carbon::parse($request->day)->format('Y-m-d'))
                ->get();
            if ($vacations->count() === 0) {
                $user->vacations()->create([
                    'from' => Carbon::parse($request->day)->format('Y-m-d'),
                    'until' => Carbon::parse($request->day)->format('Y-m-d')
                ]);
            }

            $shifts = $user->shifts()->where('event_start_day', Carbon::parse($request->day)->format('Y-m-d'))->get();
            foreach ($shifts as $shift) {
                $shift->users()->detach($user->id);
            }
        }
    }

    public function update(Request $request, UserVacations $userVacations): void
    {
        $oldFrom = $userVacations->from;
        $oldUntil = $userVacations->until;
        $userVacations->update($request->only(['from', 'until']));

        $newFrom = $userVacations->from;
        $newUntil = $userVacations->until;
        if ($oldFrom !== $newFrom) {
            $this->history->setHistoryText(
                'Verfügbarkeit geändert von ' .
                Carbon::parse($oldFrom)->format('d.m.Y') . ' zu ' .
                Carbon::parse($newFrom)->format('d.m.Y')
            );
            $this->history->setModelId($userVacations->id);
            $this->history->setType('vacation');
            $this->history->create();
        }

        if ($oldUntil !== $newUntil) {
            $this->history->setHistoryText(
                'Verfügbarkeit geändert bis: ' .
                Carbon::parse($oldUntil)->format('d.m.Y') . ' zu ' .
                Carbon::parse($newUntil)->format('d.m.Y')
            );
            $this->history->setModelId($userVacations->id);
            $this->history->setType('vacation');
            $this->history->create();
        }

        $schedule = new SchedulingController();
        $schedule->create(
            $userVacations->user()->first()->id,
            'VACATION_CHANGES',
            'USER_VACATIONS',
            $userVacations->user()->first()->id
        );
    }

    public function destroy(UserVacations $userVacations): void
    {
        $userVacations->delete();
    }
}

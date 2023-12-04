<?php

namespace App\Http\Controllers;

use App\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    public function __construct(private readonly VacationService $vacationService)
    {
    }

    public function store(Request $request, User $user): void
    {
        $this->vacationService->create(
            $user,
            Carbon::parse($request->from),
            Carbon::parse($request->until),
        );
    }

    public function checkVacation(Request $request, User $user): void
    {
        $day = Carbon::parse($request->day);
        if ($request->checked) {
            $this->vacationService->deleteVacationInterval($user, $day, $day);
            return;
        }
        $vacations = $this->vacationService->findVacationWithinInterval($user, $day, $day);
        if ($vacations->count() === 0) {
            $this->vacationService->create($user, $day, $day);
        }

        $shifts = $user->shifts()->where('event_start_day', $day)->get();
        foreach ($shifts as $shift) {
            $shift->users()->detach($user->id);
        }
    }

    public function update(Request $request, Vacation $vacation): void
    {
        $this->vacationService->update(
            $vacation,
            Carbon::parse($request->input('from')),
            Carbon::parse($request->input('until')),
        );
    }

    public function destroy(Vacation $vacation): void
    {
        $this->vacationService->delete($vacation);
    }
}

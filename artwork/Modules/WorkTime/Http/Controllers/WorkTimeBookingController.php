<?php

namespace Artwork\Modules\WorkTime\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Artwork\Modules\WorkTime\Http\Requests\RebookWorkTimeRequest;
use Artwork\Modules\WorkTime\Http\Requests\StoreWorkTimeBookingRequest;
use Artwork\Modules\WorkTime\Http\Requests\UpdateWorkTimeBookingRequest;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class WorkTimeBookingController extends Controller
{

    public function __construct(
        protected WorkTimeBookingRepository $repository,
        protected WorkingHourCacheService $workingHourCacheService,
        protected OvertimeService $overtimeService,
        protected WorkTimeBookingService $workTimeBookingService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkTimeBookingRequest $request, User $user): void
    {
        $date = Carbon::parse($request->input('date'));
        $weekday = $date->format('w');
        $isHoliday = $this->repository->isHoliday($date);

        // worked_hours
        $workedHours = $request->input('hours', '00:00');
        $workedMinutes = 0;
        if (str_contains($workedHours, ':')) {
            [$h, $m] = explode(':', $workedHours);
            $workedMinutes = (int)$h * 60 + (int)$m;
        } else {
            $workedMinutes = (int)$workedHours;
        }

        // nightly_working_hours
        $nightlyWorkedHours = $request->input('nightly_working_hours', '00:00');
        $nightlyMinutes = 0;
        if (str_contains($nightlyWorkedHours, ':')) {
            [$nh, $nm] = explode(':', $nightlyWorkedHours);
            $nightlyMinutes = (int)$nh * 60 + (int)$nm;
        } else {
            $nightlyMinutes = (int)$nightlyWorkedHours;
        }

        // Plus or minus adjustment
        $plusMinus = $request->input('plus_minus', '+');
        if ($plusMinus === '-') {
            $workedMinutes = -$workedMinutes;
        }

        $this->repository->createBookingAndUpdateBalanceInTransaction($user, [
            'booker_id' => auth()->id(),
            'name' => 'manual_booking',
            'comment' => $request->input('comment'),
            'booking_day' => $date->format('Y-m-d'),
            'booking_weekday' => $weekday,
            'wanted_working_hours' => 0,
            'worked_hours' => $workedMinutes,
            'is_special_day' => false,
            'nightly_working_hours' => $nightlyMinutes,
            'work_time_balance_change' => $workedMinutes,
        ], $workedMinutes);

        $this->workingHourCacheService->forgetForEntity('user', $user->id);

        // Überstunden sofort neu aufbauen (sonst erst mit der nächtlichen Buchung sichtbar)
        $this->overtimeService->recomputeForUser($user);
    }


    /**
     * „Tag neu buchen“: vergangene Tage nach aktueller Rechnung buchen (Delta gegen die vorhandene
     * Tagesbuchung bzw. erstmalig). Nur auf ausdrücklichen Klick – nie automatisch.
     */
    public function rebook(RebookWorkTimeRequest $request, User $user): RedirectResponse
    {
        $this->workTimeBookingService->rebookPastDays($user, array_unique($request->validated('dates')));

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkTimeBooking $workTimeBooking): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WorkTimeBooking $workTimeBooking): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkTimeBookingRequest $request, WorkTimeBooking $workTimeBooking): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkTimeBooking $workTimeBooking): void
    {
        //
    }
}

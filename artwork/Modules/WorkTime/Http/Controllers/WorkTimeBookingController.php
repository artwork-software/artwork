<?php

namespace Artwork\Modules\WorkTimeBooking\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTimeBooking\Http\Requests\StoreWorkTimeBookingRequest;
use Artwork\Modules\WorkTimeBooking\Http\Requests\UpdateWorkTimeBookingRequest;
use Artwork\Modules\WorkTimeBooking\Models\WorkTimeBooking;
use Artwork\Modules\WorkTimeBooking\Repositories\WorkTimeBookingRepository;
use Carbon\Carbon;

class WorkTimeBookingController extends Controller
{

    public function __construct(
        protected WorkTimeBookingRepository $repository,
    ) { }

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

        $user->workTimeBookings()->create([
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
        ]);

        $this->repository->updateUserBalance($user, $workedMinutes);
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

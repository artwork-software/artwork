<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypeResource;
use App\Http\Resources\FreelancerShowResource;
use App\Models\Craft;
use App\Models\EventType;
use App\Models\Freelancer;
use App\Models\Project;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FreelancerController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(): \Symfony\Component\HttpFoundation\Response
    {
        $freelancer = Freelancer::create(
            ['profile_image' => 'https://ui-avatars.com/api/?name=NEU&color=7F9CF5&background=EBF4FF']
        );

        return Inertia::location(route('freelancer.show', $freelancer->id));
    }

    /**
     * @param Freelancer $freelancer
     * @param $month
     * @return array
     */
    function getAvailabilityData(Freelancer $freelancer, $month = null): array
    {
        $vacationDays = $freelancer->vacations()->orderBy('from', 'ASC')->get();

        $currentMonth = Carbon::now()->startOfMonth();

        if ($month) {
            $currentMonth = Carbon::parse($month)->startOfMonth();
        }

        $startDate = $currentMonth->copy()->startOfWeek();
        $endDate = $currentMonth->copy()->endOfMonth()->endOfWeek();

        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $onVacation = false;
            $weekNumber = $currentDate->weekOfYear;
            $day = $currentDate->day;
            foreach ($vacationDays as $vacationDay) {
                $vacationStart = Carbon::parse($vacationDay->from);
                $vacationEnd = Carbon::parse($vacationDay->until);
                // TODO: Check Performance
                /*if($currentDate < $vacationStart){
                    $onVacation = false;
                    continue;
                }*/
                if ($vacationStart <= $currentDate && $vacationEnd >= $currentDate) {
                    $onVacation = true;
                }
            }

            if (!isset($calendarData[$weekNumber])) {
                $calendarData[$weekNumber] = ['weekNumber' => $weekNumber, 'days' => []];
            }

            $notInMonth = !$currentDate->isSameMonth($currentMonth);

            $calendarData[$weekNumber]['days'][] = [
                'day' => $day,
                'notInMonth' => $notInMonth,
                'onVacation' => $onVacation
            ];

            $currentDate->addDay();
        }

        $dateToShow = [
            $currentMonth->locale('de')->isoFormat('MMMM YYYY'),
            $currentMonth->copy()->startOfMonth()->toDate()
        ];

        return [
            'calendarData' => array_values($calendarData),
            'dateToShow' => $dateToShow
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param Freelancer $freelancer
     * @param CalendarController $shiftPlan
     * @return Response
     */
    public function show(Freelancer $freelancer, CalendarController $shiftPlan): Response
    {
        $showCalendar = $shiftPlan->createCalendarDataForFreelancerShiftPlan($freelancer);
        $availabilityData = $this->getAvailabilityData($freelancer, request('month'));

        return inertia('Freelancer/Show', [
            'freelancer' => new FreelancerShowResource($freelancer),
            //needed for availability calendar
            'calendarData' => $availabilityData['calendarData'],
            'dateToShow' => $availabilityData['dateToShow'],
            'vacations' => $freelancer->vacations()->orderBy('from', 'ASC')->get(),
            //needed for UserShiftPlan
            'dateValue' => $showCalendar['dateValue'],
            'daysWithEvents' => $showCalendar['daysWithEvents'],
            'totalPlannedWorkingHours' => $showCalendar['totalPlannedWorkingHours'],
            'rooms' => Room::all(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => Project::all(),
            'shifts' => $freelancer
                ->shifts()
                ->with(['event', 'event.project', 'event.room'])
                ->orderBy('start', 'ASC')
                ->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Freelancer $freelancer
     */
    public function update(Request $request, Freelancer $freelancer): void
    {
        $freelancer->update($request->only([
                'position',
                'first_name',
                'last_name',
                'email',
                'phone_number',
                'street',
                'zip_code',
                'location',
                'note',
                'salary_per_hour',
                'salary_description',
                'can_master'
            ]));
    }

    /**
     * @param Freelancer $freelancer
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateWorkProfile(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $freelancer->update([
            'work_name' => $request->get('workName'),
            'work_description' => $request->get('workDescription')
        ]);

        return Redirect::back()->with('success', ['workProfile' => 'Arbeitsprofil erfolgreich aktualisiert']);
    }

    /**
     * @param Freelancer $freelancer
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateCraftSettings(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $freelancer->update([
            'can_work_shifts' => $request->boolean('canBeAssignedToShifts'),
            'can_master' => $request->boolean('canBeUsedAsMasterCraftsman')
        ]);

        return Redirect::back();
    }

    /**
     * @param Freelancer $freelancer
     * @param Request $request
     * @return RedirectResponse
     */
    public function assignCraft(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $craftToAssign = Craft::find($request->get('craftId'));

        if (is_null($craftToAssign)) {
            return Redirect::back();
        }

        if (!$freelancer->assigned_crafts->contains($craftToAssign)) {
            $freelancer->assigned_crafts()->attach(Craft::find($request->get('craftId')));
        }

        return Redirect::back()->with('success', ['craft' => 'Gewerk erfolgreich zugeordnet.']);
    }

    /**
     * @param Freelancer $freelancer
     * @param Craft $craft
     * @return RedirectResponse
     */
    public function removeCraft(Freelancer $freelancer, Craft $craft): RedirectResponse
    {
        $freelancer->assigned_crafts()->detach($craft);

        return Redirect::back()->with('success', ['craft' => 'Gewerk erfolgreich entfernt.']);
    }

    /**
     * @param Request $request
     * @param Freelancer $freelancer
     * @return void
     */
    public function updateProfileImage(Request $request, Freelancer $freelancer): void
    {
        if (!Storage::exists("public/profile-photos")) {
            Storage::makeDirectory("public/profile-photos");
        }

        $file = $request->file('profileImage');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('public/profile-photos', $file, $basename);

        $freelancer->update(['profile_image' => Storage::url('public/profile-photos/' . $basename)]);
    }
}

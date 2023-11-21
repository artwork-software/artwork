<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypeResource;
use App\Models\EventType;
use App\Models\ServiceProvider;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceProviderController extends Controller
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $serviceProvider = ServiceProvider::create(['profile_image' => 'https://ui-avatars.com/api/?name=NEU&color=7F9CF5&background=EBF4FF']);

        return Inertia::location(route('service_provider.show', $serviceProvider->id));
    }

    /**
     * Display the specified resource.
     *
     * @param ServiceProvider $serviceProvider
     * @return Response
     */
    public function show(ServiceProvider $serviceProvider, CalendarController $shiftPlan): \Inertia\Response
    {
        //$shiftPlan = new CalendarController();
        $showCalendar = $shiftPlan->createCalendarDataForServiceProviderShiftPlan($serviceProvider);

        return Inertia::render('ServiceProvider/Show', [
            'serviceProvider' => $serviceProvider,
            //needed for UserShiftPlan
            'dateValue'=> $showCalendar['dateValue'],
            'daysWithEvents' => $showCalendar['daysWithEvents'],
            'totalPlannedWorkingHours' => $showCalendar['totalPlannedWorkingHours'],
            'rooms' => Room::all(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => Project::all(),
            'shifts' => $serviceProvider->shifts()->with(['event', 'event.project', 'event.room'])->orderBy('start', 'ASC')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ServiceProvider  $serviceProvider
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceProvider $serviceProvider)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServiceProvider  $serviceProvider
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ServiceProvider $serviceProvider)
    {
        $serviceProvider->update($request->only([
            'provider_name',
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

    public function update_provider_can_master(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $serviceProvider->update([
            'can_master' => $request->can_master
        ]);

        return Redirect::back()->with('success', 'Service provider updated');
    }

    public function update_work_data(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $serviceProvider->update([
            'work_name' => $request->work_name,
            'work_description' => $request->work_description
        ]);

        return Redirect::back()->with('success', 'Service provider updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceProvider  $serviceProvider
     * @return \Illuminate\Http\Response
     */
    public function destroy(ServiceProvider $serviceProvider)
    {
        //
    }


    public function updateProfileImage(Request $request, ServiceProvider $serviceProvider): void
    {
        if (!Storage::exists("public/profile-photos")) {
            Storage::makeDirectory("public/profile-photos");
        }

        $file = $request->file('profileImage');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20).$original_name;

        Storage::putFileAs('public/profile-photos', $file, $basename);

        $serviceProvider->update(['profile_image' => Storage::url('public/profile-photos/' . $basename)]);

    }
}

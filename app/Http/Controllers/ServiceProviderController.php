<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypeResource;
use App\Http\Resources\ServiceProviderShowResource;
use App\Models\Craft;
use App\Models\EventType;
use App\Models\Project;
use App\Models\Room;
use App\Models\ServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceProviderController extends Controller
{
    public function index(): void
    {
    }

    public function create(): void
    {
    }

    public function store(): \Symfony\Component\HttpFoundation\Response
    {
        $serviceProvider = ServiceProvider::create(
            ['profile_image' => 'https://ui-avatars.com/api/?name=NEU&color=7F9CF5&background=EBF4FF']
        );

        return Inertia::location(route('service_provider.show', $serviceProvider->id));
    }

    public function show(ServiceProvider $serviceProvider, CalendarController $shiftPlan): Response
    {
        $showCalendar = $shiftPlan->createCalendarDataForServiceProviderShiftPlan($serviceProvider);

        return Inertia::render('ServiceProvider/Show', [
            'serviceProvider' => new ServiceProviderShowResource($serviceProvider),
            'dateValue' => $showCalendar['dateValue'],
            'daysWithEvents' => $showCalendar['daysWithEvents'],
            'totalPlannedWorkingHours' => $showCalendar['totalPlannedWorkingHours'],
            'rooms' => Room::all(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => Project::all(),
            'shifts' => $serviceProvider
                ->shifts()
                ->with(['event', 'event.project', 'event.room'])
                ->orderBy('start', 'ASC')
                ->get(),
        ]);
    }

    public function edit(ServiceProvider $serviceProvider): void
    {
    }

    public function update(Request $request, ServiceProvider $serviceProvider): void
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

    public function updateWorkProfile(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $serviceProvider->update([
            'work_name' => $request->get('workName'),
            'work_description' => $request->get('workDescription')
        ]);

        return Redirect::back()->with('success', ['workProfile' => 'Arbeitsprofil erfolgreich aktualisiert']);
    }

    public function updateCraftSettings(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $serviceProvider->update([
            'can_work_shifts' => $request->boolean('canBeAssignedToShifts'),
            'can_master' => $request->boolean('canBeUsedAsMasterCraftsman')
        ]);

        return Redirect::back();
    }

    public function assignCraft(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $craftToAssign = Craft::find($request->get('craftId'));

        if (is_null($craftToAssign)) {
            return Redirect::back();
        }

        if (!$serviceProvider->assignedCrafts->contains($craftToAssign)) {
            $serviceProvider->assignedCrafts()->attach(Craft::find($request->get('craftId')));
        }

        return Redirect::back()->with('success', ['craft' => 'Gewerk erfolgreich zugeordnet.']);
    }

    public function removeCraft(ServiceProvider $serviceProvider, Craft $craft): RedirectResponse
    {
        $serviceProvider->assignedCrafts()->detach($craft);

        return Redirect::back()->with('success', ['craft' => 'Gewerk erfolgreich entfernt.']);
    }

    public function destroy(): void
    {
    }

    public function updateProfileImage(Request $request, ServiceProvider $serviceProvider): void
    {
        if (!Storage::exists("public/profile-photos")) {
            Storage::makeDirectory("public/profile-photos");
        }

        $file = $request->file('profileImage');
        $original_name = $file->getClientOriginalName();
        $basename = Str::random(20) . $original_name;

        Storage::putFileAs('public/profile-photos', $file, $basename);

        $serviceProvider->update(['profile_image' => Storage::url('public/profile-photos/' . $basename)]);
    }
}

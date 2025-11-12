<?php

namespace App\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Http\Requests\UpdateServiceProviderShiftQualificationRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ServiceProviderShiftQualificationService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceProviderController extends Controller
{

    public function __construct(
        protected GlobalQualificationService $globalQualificationService,
    ) {
    }

    public function store(): \Symfony\Component\HttpFoundation\Response
    {
        $serviceProvider = ServiceProvider::create();

        return Inertia::location(route('service_provider.show', $serviceProvider->id));
    }

    public function show(
        ServiceProvider $serviceProvider,
        ServiceProviderService $serviceProviderService,
        UserService $userService,
        EventService $eventService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        ShiftQualificationService $shiftQualificationService
    ): Response {

        $globalQualifications = $this->globalQualificationService
            ->getAll()->map(function ($qualification) use ($serviceProvider) {
                return [
                    'id' => $qualification->id,
                    'name' => $qualification->name,
                    'icon' => $qualification->icon,
                    'assigned' => $serviceProvider->globalQualifications->contains('id', $qualification->id),
                ];
            });

        // check if $globalQualifications is not empty
        if (!$globalQualifications->isEmpty()) {
            Inertia::share([
                'globalQualifications' => $globalQualifications,
            ]);
        }



        return Inertia::render(
            'ServiceProvider/Show',
            $serviceProviderService->createShowDto(
                $serviceProvider,
                $userService,
                $eventService,
                $roomService,
                $eventTypeService,
                $projectService,
                $shiftQualificationService
            )
        );
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
            'type_of_provider'
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateTerms(ServiceProvider $serviceProvider, Request $request): void
    {
        $this->authorize('updateTerms', ServiceProvider::class);

        $serviceProvider->update($request->only([
            'salary_per_hour',
            'salary_description',
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateWorkProfile(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->update([
            'work_name' => $request->get('workName'),
            'work_description' => $request->get('workDescription')
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateCraftSettings(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->update([
            'can_work_shifts' => $request->boolean('canBeAssignedToShifts')
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateShiftQualification(
        \Artwork\Modules\ServiceProvider\Models\ServiceProvider $serviceProvider,
        \Artwork\Modules\Shift\Models\GlobalQualification $qualification,
        \Artwork\Modules\Shift\Services\GlobalQualificationService $qualificationService
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('updateWorkProfile', \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class);
        $qualificationService->activateOrDeactivateInQualifiable($qualification, $serviceProvider);
        return \Illuminate\Support\Facades\Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function assignCraft(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->assignedCrafts()->attach($request->get('craftId'));

        /*$craftToAssign = Craft::find($request->get('craftId'));

        if (is_null($craftToAssign)) {
            return Redirect::back();
        }

        if (!$serviceProvider->assignedCrafts->contains($craftToAssign)) {
            $serviceProvider->assignedCrafts()->attach(Craft::find($request->get('craftId')));
        }*/

        return Redirect::back();
    }

    public function assignCraftsBulk(ServiceProvider $serviceProvider, Request $request): RedirectResponse
    {
        $craftsToAssign = Craft::whereIn('id', $request->get('craftIds'))->get();

        foreach ($craftsToAssign as $craft) {
            if (!$serviceProvider->assignedCrafts->contains($craft)) {
                $serviceProvider->assignedCrafts()->attach($craft);
            }
        }

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function removeCraft(ServiceProvider $serviceProvider, Craft $craft): RedirectResponse
    {
        $this->authorize('updateWorkProfile', ServiceProvider::class);

        $serviceProvider->assignedCrafts()->detach($craft);

        return Redirect::back();
    }

    public function destroy(ServiceProvider $serviceProvider): RedirectResponse
    {
        $serviceProvider->delete();

        return Redirect::back();
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

    /**
     * Toggle a shift qualification for a service provider in a specific craft (morphToMany pivot mit craft_id)
     */
    public function updateCraftShiftQualification(
        ServiceProvider $serviceProvider,
        Craft $craft,
        ShiftQualification $qualification
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('updateWorkProfile', \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class);
        $pivotExists = $serviceProvider->shiftQualifications()
            ->wherePivot('craft_id', $craft->id)
            ->where('shift_qualification_id', $qualification->id)->exists();
        if ($pivotExists) {
            $serviceProvider->shiftQualifications()->newPivotStatement()
                ->where('qualifiable_id', $serviceProvider->id)
                ->where('qualifiable_type', $serviceProvider->getMorphClass())
                ->where('shift_qualification_id', $qualification->id)
                ->where('craft_id', $craft->id)
                ->delete();
        } else {
            $serviceProvider->shiftQualifications()->attach($qualification->id, [
                'craft_id' => $craft->id
            ]);
        }
        return \Illuminate\Support\Facades\Redirect::back();
    }
}

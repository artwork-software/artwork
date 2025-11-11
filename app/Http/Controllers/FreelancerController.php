<?php

namespace App\Http\Controllers;

use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Shift\Http\Requests\UpdateFreelancerShiftQualificationRequest;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\FreelancerShiftQualificationService;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Config\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class FreelancerController extends Controller
{

    public function __construct(
        protected GlobalQualificationService $globalQualificationService,
    ) {
    }

    public function store(): \Symfony\Component\HttpFoundation\Response
    {
        $freelancer = Freelancer::create();

        return Inertia::location(route('freelancer.show', $freelancer->id));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function show(
        Request $request,
        SessionManager $sessionManager,
        Repository $config,
        Freelancer $freelancer,
        FreelancerService $freelancerService,
        UserService $userService,
        EventService $eventService,
        CalendarService $calendarService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        ShiftQualificationService $shiftQualificationService,
    ): Response {
        $showVacationsAndAvailabilities = $request->get('showVacationsAndAvailabilities');
        $vacationMonth = $request->get('vacationMonth');
        $selectedDate = $showVacationsAndAvailabilities ?
            Carbon::parse($showVacationsAndAvailabilities) :
            Carbon::today();
        $selectedPeriodDate = $vacationMonth ?
            Carbon::parse($vacationMonth) :
            Carbon::today();

        $selectedPeriodDate->locale($sessionManager->get('locale') ?? $config->get('app.fallback_locale'));

        $globalQualifications = $this->globalQualificationService
            ->getAll()->map(function ($qualification) use ($freelancer) {
                return [
                'id' => $qualification->id,
                'name' => $qualification->name,
                'icon' => $qualification->icon,
                'assigned' => $freelancer->globalQualifications->contains('id', $qualification->id),
                ];
            });

        // check if $globalQualifications is not empty
        if (!$globalQualifications->isEmpty()) {
            Inertia::share([
                'globalQualifications' => $globalQualifications,
            ]);
        }




        return Inertia::render(
            'Freelancer/Show',
            $freelancerService->createShowDto(
                $freelancer,
                $userService,
                $eventService,
                $calendarService,
                $roomService,
                $eventTypeService,
                $projectService,
                $shiftQualificationService,
                $selectedDate,
                $selectedPeriodDate,
                $request->get('month'),
                $vacationMonth
            )
        );
    }

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
            ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateTerms(Freelancer $freelancer, Request $request): void
    {
        $this->authorize('updateTerms', Freelancer::class);

        $freelancer->update($request->only([
            'salary_per_hour',
            'salary_description',
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateWorkProfile(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', Freelancer::class);

        $freelancer->update([
            'work_name' => $request->get('workName'),
            'work_description' => $request->get('workDescription')
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateCraftSettings(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', Freelancer::class);

        $freelancer->update([
            'can_work_shifts' => $request->boolean('canBeAssignedToShifts')
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateShiftQualification(
        \Artwork\Modules\Freelancer\Models\Freelancer $freelancer,
        \Artwork\Modules\Shift\Models\GlobalQualification $qualification,
        \Artwork\Modules\Shift\Services\GlobalQualificationService $qualificationService
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('updateWorkProfile', \Artwork\Modules\Freelancer\Models\Freelancer::class);
        $qualificationService->activateOrDeactivateInQualifiable($qualification, $freelancer);
        return \Illuminate\Support\Facades\Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function assignCraft(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', Freelancer::class);

        $freelancer->assignedCrafts()->attach($request->get('craftId'));
        /*$craftToAssign = Craft::find($request->get('craftId'));

        if (is_null($craftToAssign)) {
            return Redirect::back();
        }

        if (!$freelancer->assignedCrafts->contains($craftToAssign)) {
            $freelancer->assignedCrafts()->attach(Craft::find($request->get('craftId')));
        }*/

        return Redirect::back();
    }

    public function assignCraftsBulk(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $craftIds = $request->get('craftIds', []);
        $freelancer->assignedCrafts()->syncWithoutDetaching($craftIds);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function removeCraft(Freelancer $freelancer, Craft $craft): RedirectResponse
    {
        $this->authorize('updateWorkProfile', Freelancer::class);

        $freelancer->assignedCrafts()->detach($craft);

        return Redirect::back();
    }

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

    public function destroy(Freelancer $freelancer): RedirectResponse
    {
        $freelancer->delete();

        return Redirect::back();
    }

    /**
     * Toggle a shift qualification for a freelancer in a specific craft (morphToMany pivot mit craft_id)
     */
    public function updateCraftShiftQualification(
        Freelancer $freelancer,
        Craft $craft,
        ShiftQualification $qualification
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('updateWorkProfile', \Artwork\Modules\Freelancer\Models\Freelancer::class);
        $pivotExists = $freelancer->shiftQualifications()
            ->wherePivot('craft_id', $craft->id)
            ->where('shift_qualification_id', $qualification->id)->exists();
        if ($pivotExists) {
            $freelancer->shiftQualifications()->newPivotStatement()
                ->where('qualifiable_id', $freelancer->id)
                ->where('qualifiable_type', $freelancer->getMorphClass())
                ->where('shift_qualification_id', $qualification->id)
                ->where('craft_id', $craft->id)
                ->delete();
        } else {
            $freelancer->shiftQualifications()->attach($qualification->id, [
                'craft_id' => $craft->id
            ]);
        }
        return \Illuminate\Support\Facades\Redirect::back();
    }
}

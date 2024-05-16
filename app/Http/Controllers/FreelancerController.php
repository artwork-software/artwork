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
use Artwork\Modules\ShiftQualification\Http\Requests\UpdateFreelancerShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Services\FreelancerShiftQualificationService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
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
    public function store(): \Symfony\Component\HttpFoundation\Response
    {
        $freelancer = Freelancer::create(
            ['profile_image' => 'https://ui-avatars.com/api/?name=NEU&color=7F9CF5&background=EBF4FF']
        );

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
        Freelancer $freelancer,
        UpdateFreelancerShiftQualificationRequest $request,
        FreelancerShiftQualificationService $freelancerShiftQualificationService
    ): RedirectResponse {
        $this->authorize('updateWorkProfile', Freelancer::class);

        if ($request->boolean('create')) {
            //if useable is set to true create a new entry in pivot table
            $freelancerShiftQualificationService->createByRequestForFreelancer($request, $freelancer);
        } else {
            //if useable is set to false pivot table entry needs to be deleted
            $freelancerShiftQualificationService->deleteByRequestForFreelancer($request, $freelancer);
        }

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function assignCraft(Freelancer $freelancer, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', Freelancer::class);

        $craftToAssign = Craft::find($request->get('craftId'));

        if (is_null($craftToAssign)) {
            return Redirect::back();
        }

        if (!$freelancer->assignedCrafts->contains($craftToAssign)) {
            $freelancer->assignedCrafts()->attach(Craft::find($request->get('craftId')));
        }

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
}

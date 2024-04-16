<?php

namespace App\Http\Controllers;

use App\Enums\PermissionNameEnum;
use App\Enums\RoleNameEnum;
use App\Events\UserUpdated;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\UserIndexResource;
use App\Http\Resources\UserShowResource;
use App\Http\Resources\UserWorkProfileResource;
use App\Models\Craft;
use App\Models\EventType;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\User;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\PermissionPresets\Services\PermissionPresetService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ShiftQualification\Http\Requests\UpdateUserShiftQualificationRequest;
use Artwork\Modules\ShiftQualification\Repositories\ShiftQualificationRepository;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\ShiftQualification\Services\UserShiftQualificationService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Response;
use Inertia\ResponseFactory;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Artwork\Modules\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendarService
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * @return array<string, mixed>
     * @throws AuthorizationException
     */
    public function search(SearchRequest $request): array
    {
        return UserIndexResource::collection(User::nameOrLastNameLike($request->get('query'))->get())->resolve();
    }

    /**
     * @param SearchRequest $request
     * @return User[]
     */
    public function moneySourceSearch(SearchRequest $request): array
    {
        $wantedUserArray = [];

        $wantedUsers = User::search($request->input('query'))->get();
        foreach ($wantedUsers as $user) {
            $wantedUserArray[] = $user;
        }
        return $wantedUserArray;
    }

    /**
     * @return Application|RedirectResponse|mixed
     * @throws AuthorizationException
     */
    public function resetUserPassword(Request $request): mixed
    {
        $this->authorize('update', User::class);

        $request->validate([Fortify::email() => 'required|email']);

        $status = Password::broker()->sendResetLink(
            $request->only(Fortify::email())
        );

        return $status == Password::RESET_LINK_SENT
            ? Redirect::back()
                ->with('status', __('passwords.sentToUser', [], Auth::user()->language))
            : app(FailedPasswordResetLinkRequestResponse::class, ['status' => $status]);
    }


    public function resetPassword(): Response|ResponseFactory
    {
        $token = request('token');
        $email = request('email');

        return inertia('Auth/ResetPassword', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function index(PermissionPresetService $permissionPresetService): Response|ResponseFactory
    {
        return inertia('Users/Index', [
            'users' => UserIndexResource::collection(User::all())->resolve(),
            'all_permissions' => Permission::all()->groupBy('group'),
            'departments' => Department::all(),
            'roles' => Role::all(),
            'freelancers' => Freelancer::all(),
            'serviceProviders' => ServiceProvider::all(),
            'permission_presets' => $permissionPresetService->getPermissionPresets()
        ]);
    }

    public function editUserInfo(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserInfoPage', [
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'info',
            "departments" => Department::all(),
            "password_reset_status" => session('status')
        ]);
    }

    public function editUserShiftplan(
        User $user,
        CalendarController $shiftPlan,
        ShiftQualificationService $shiftQualificationService
    ): Response|ResponseFactory {
        $showCalendar = $shiftPlan->createCalendarDataForUserShiftPlan($user);
        //$this->getAvailabilityData($user, request('month'))
        $availabilityData = $this->calendarService
            ->getAvailabilityData(user: $user, month: request('month'));

        $selectedDate = Carbon::today();
        $selectedPeriodDate = Carbon::today();
        $vacations = [];
        // get vacations of the selected date (request('showVacationsAndAvailabilities'))
        if (request('showVacationsAndAvailabilities')) {
            $selectedDate = Carbon::parse(request('showVacationsAndAvailabilities'));
        }

        if (request('vacationMonth')) {
            $selectedPeriodDate = Carbon::parse(request('vacationMonth'));
        }

        $vacations = $user->vacations()
            ->where('date', $selectedDate)
            ->orderBy('date', 'ASC')->get();

        $availabilities = $user->availabilities()
            ->where('date', $selectedDate)
            ->orderBy('date', 'ASC')->get();

        $createShowDate = [
            $selectedPeriodDate->locale(
                \session()->get('locale') ??
                    config('app.fallback_locale')
            )->isoFormat('MMMM YYYY'),
            $selectedPeriodDate->copy()->startOfMonth()->toDate()
        ];

        return inertia('Users/UserShiftPlanPage', [
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'shiftplan',
            'calendarData' => $availabilityData['calendarData'],
            'dateToShow' => $availabilityData['dateToShow'],
            'vacationSelectCalendar' => $this->calendarService
                ->createVacationAndAvailabilityPeriodCalendar(request('vacationMonth')),
            'createShowDate' => $createShowDate,
            'vacations' => $vacations,
            'availabilities' => $availabilities,
            'showVacationsAndAvailabilitiesDate' => $selectedDate->format('Y-m-d'),
            'dateValue' => $showCalendar['dateValue'],
            'daysWithEvents' => $showCalendar['daysWithEvents'],
            'totalPlannedWorkingHours' => $showCalendar['totalPlannedWorkingHours'],
            'rooms' => Room::all(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => Project::all(),
            'shifts' => $user
                ->shifts()
                ->with(['event', 'event.project', 'event.room'])
                ->orderBy('start', 'ASC')
                ->get(),
            'shiftQualifications' => $shiftQualificationService->getAllOrderedByCreationDateAscending()
        ]);
    }

    public function editUserTerms(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserTermsPage', [
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'terms',
        ]);
    }

    public function editUserPermissions(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserPermissionsPage', [
            'user_to_edit' => new UserShowResource($user),
            'available_roles' => Role::all(),
            "all_permissions" => Permission::all()->groupBy('group'),
            'currentTab' => 'permissions',
        ]);
    }

    public function editUserWorkProfile(
        User $user,
        ShiftQualificationRepository $shiftQualificationRepository
    ): Response|ResponseFactory {
        return inertia(
            'Users/UserWorkProfilePage',
            [
                'userToEdit' => new UserWorkProfileResource($user),
                'currentTab' => 'workProfile',
                'shiftQualifications' => $shiftQualificationRepository->getAllAvailableOrderedByCreationDateAscending()
            ]
        );
    }

    public function updateUserPhoto(User $user, Request $request): void
    {
        if (isset($request['photo'])) {
            $user->updateProfilePhoto($request['photo']);
        }
    }

    /**
     * @param User $user
     * @param $month
     * @return array<string, mixed>
     */
    private function getAvailabilityData(User $user, $month = null): array
    {
        $vacationDays = $user->vacations()->orderBy('date', 'ASC')->get();

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
                if ($currentDate->isSameDay($vacationDay->date)) {
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
                'onVacation' => $onVacation,
                'day_formatted' => $currentDate->format('Y-m-d'),
            ];

            $currentDate->addDay();
        }

        $dateToShow = [
            $currentMonth->locale(\session()->get('locale') ?? config('app.fallback_locale'))->isoFormat('MMMM YYYY'),
            $currentMonth->copy()->startOfMonth()->toDate()
        ];

        return [
            'calendarData' => array_values($calendarData),
            'dateToShow' => $dateToShow
        ];
    }

    public function updateUserDetails(Request $request, User $user): RedirectResponse
    {
        if ($user->id !== Auth::user()->id && !Auth::user()->can(PermissionNameEnum::TEAM_UPDATE->value)) {
            abort(\Illuminate\Http\Response::HTTP_FORBIDDEN);
        }
        $user->update(
            $request->only('first_name', 'last_name', 'phone_number', 'position', 'description', 'email', 'language')
        );

        if (Auth::user()->can(PermissionNameEnum::TEAM_UPDATE->value)) {
            $user->departments()->sync(
                collect($request->departments)
                    ->map(function ($department) {
                        return $department['id'];
                    })
            );
        }

        Session::put('locale', $user->language);

        return Redirect::back();
    }

    public function updateUserPermissionsAndRoles(Request $request, User $user): RedirectResponse
    {
        //only add permissions which are also existing to the array which gets synced with user
        $availablePermissions = PermissionNameEnum::cases();
        $permissionsToGrant = [];
        foreach ($request->permissions as $permissionToGrant) {
            foreach ($availablePermissions as $availablePermission) {
                if ($availablePermission->value === $permissionToGrant) {
                    $permissionsToGrant[] = $permissionToGrant;
                }
            }
        }

        //only add roles which are also existing to the array which gets synced with user
        $availableRoles = RoleNameEnum::cases();
        $rolesToGrant = [];
        foreach ($request->roles as $roleToGrant) {
            foreach ($availableRoles as $availableRole) {
                if ($availableRole->value === $roleToGrant) {
                    $rolesToGrant[] = $roleToGrant;
                }
            }
        }

        $user->syncPermissions($permissionsToGrant);
        $user->syncRoles($rolesToGrant);

        return Redirect::back();
    }

    public function updateChecklistStatus(Request $request): RedirectResponse
    {
        Auth::user()->update([
            'opened_checklists' => $request->opened_checklists
        ]);

        return Redirect::back();
    }

    public function updateAreaStatus(Request $request): RedirectResponse
    {
        Auth::user()->update([
            'opened_areas' => $request->opened_areas
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateWorkProfile(User $user, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', User::class);

        $user->update([
            'work_name' => $request->get('workName'),
            'work_description' => $request->get('workDescription')
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateCraftSettings(User $user, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', User::class);

        $user->update([
            'can_work_shifts' => $request->boolean('canBeAssignedToShifts'),
        ]);

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function updateShiftQualification(
        User $user,
        UpdateUserShiftQualificationRequest $request,
        UserShiftQualificationService $userShiftQualificationService
    ): RedirectResponse {
        $this->authorize('updateWorkProfile', User::class);

        if ($request->boolean('create')) {
            //if useable is set to true create a new entry in pivot table
            $userShiftQualificationService->createByRequestForUser($request, $user);
        } else {
            //if useable is set to false pivot table entry needs to be deleted
            $userShiftQualificationService->deleteByRequestForUser($request, $user);
        }

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function assignCraft(User $user, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', User::class);

        $craftToAssign = Craft::find($request->get('craftId'));

        if (is_null($craftToAssign)) {
            return Redirect::back();
        }

        if (!$user->assignedCrafts->contains($craftToAssign)) {
            $user->assignedCrafts()->attach(Craft::find($request->get('craftId')));
        }

        return Redirect::back();
    }

    /**
     * @throws AuthorizationException
     */
    public function removeCraft(User $user, Craft $craft): RedirectResponse
    {
        $this->authorize('updateWorkProfile', User::class);

        $user->assignedCrafts()->detach($craft);

        return Redirect::back();
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->departments()->detach();
        $user->delete();

        broadcast(new UserUpdated())->toOthers();

        return Redirect::route('users');
    }


    public function temporaryUserUpdate(User $user, Request $request): void
    {
        $user->update($request->only([
            'temporary',
            'employStart',
            'employEnd'
        ]));
    }


    /**
     * @throws AuthorizationException
     */
    public function updateUserTerms(User $user, Request $request): void
    {
        $this->authorize('updateTerms', User::class);

        $user->update($request->only([
            'weekly_working_hours',
            'salary_per_hour',
            'salary_description',
        ]));
    }


    public function updateCalendarSettings(User $user, Request $request): void
    {
        $user->calendar_settings()->update($request->only([
            'project_status',
            'options',
            'project_management',
            'repeating_events',
            'work_shifts'
        ]));
    }

    public function updateSidebar(User $user, Request $request): void
    {
        $user->update($request->only([
            'is_sidebar_opened'
        ]));
    }

    public function updateZoomFactor(User $user, Request $request): void
    {
        $user->update($request->only('zoom_factor'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Casts\GermanTimeCast;
use App\Enums\PermissionNameEnum;
use App\Events\UserUpdated;
use App\Http\Requests\SearchRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\EventTypeResource;
use App\Http\Resources\UserIndexResource;
use App\Http\Resources\UserShowResource;
use App\Http\Resources\UserWorkProfileResource;
use App\Models\Craft;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Freelancer;
use App\Models\Project;
use App\Models\Room;
use App\Models\ServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * @param SearchRequest $request
     * @return array
     * @throws AuthorizationException
     */
    public function search(SearchRequest $request): array
    {

       $this->authorize('viewAny',User::class);

        return UserIndexResource::collection(User::search($request->input('query'))->get())->resolve();
    }

    /**
     * @param SearchRequest $request
     * @return array
     */
    public function money_source_search(SearchRequest $request): array
    {

        //$this->authorize('viewAny',User::class);
        $wantedUserArray = [];

        $wantedUsers = User::search($request->input('query'))->get();
        foreach ($wantedUsers as $user){
            $wantedUserArray[] = $user;
        }
        return $wantedUserArray;
    }

    /**
     * @param Request $request
     * @return Application|RedirectResponse|mixed
     * @throws AuthorizationException
     */
    public function reset_user_password(Request $request): mixed
    {

        //$user = Auth::user();

        $this->authorize('update',User::class);

        $request->validate([Fortify::email() => 'required|email']);

        $status = Password::broker()->sendResetLink(
            $request->only(Fortify::email())
        );

        /*if($user->email === $request->email){
            Auth::logout();
        }*/

        return $status == Password::RESET_LINK_SENT
            ? Redirect::back()->with('status', __('passwords.sent_to_user', ['email' => $request->email]))
            : app(FailedPasswordResetLinkRequestResponse::class, ['status' => $status]);
    }

    /**
     * @return Response|ResponseFactory
     */
    public function reset_password(): Response|ResponseFactory
    {
        $token = request('token');
        $email = request('email');

        return inertia('Auth/ResetPassword', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response|ResponseFactory
     */
    public function index(): Response|ResponseFactory
    {
        return inertia('Users/Index', [
            'users' => UserIndexResource::collection(User::all())->resolve(),
            "all_permissions" => Permission::all()->groupBy('group'),
            "departments" => Department::all(),
            "roles" => Role::all(),
            'freelancers' => Freelancer::all(),
            'serviceProviders' => ServiceProvider::all()
        ]);
    }

    /**
     * @param User $user
     * @return Response|ResponseFactory
     */
    public function editUserInfo(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserInfoPage', [
            //needed for UserEditHeader
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'info',

            "departments" => Department::all(),
            "password_reset_status" => session('status'),

        ]);
    }

    /**
     * @param User $user
     * @param CalendarController $shiftPlan
     * @return Response|ResponseFactory
     */
    public function editUserShiftplan(User $user, CalendarController $shiftPlan): Response|ResponseFactory
    {

        $showCalendar = $shiftPlan->createCalendarDataForUserShiftPlan($user);
        $availabilityData = $this->getAvailabilityData($user, request('month'));

        /*if(\request('startDate') && \request('endDate')){

            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();

        }else{

            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();

        }

        $events = Event::with(['shifts','event_type'])
            ->whereHas('shifts', function ($query) {
                $query->whereNotNull('shifts.id');
            })
            ->get();*/

        return inertia('Users/UserShiftPlanPage', [
            //needed for UserEditHeader
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'shiftplan',
            //needed for availability calendar
            'calendarData' => $availabilityData['calendarData'],
            'dateToShow' => $availabilityData['dateToShow'],
            'vacations' => $user->vacations()->orderBy('from', 'ASC')->get(),
            //needed for UserShiftPlan
            'dateValue'=> $showCalendar['dateValue'],
            'daysWithEvents' => $showCalendar['daysWithEvents'],
            'totalPlannedWorkingHours' => $showCalendar['totalPlannedWorkingHours'],
            'rooms' => Room::all(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => Project::all(),
            'shifts' => $user->shifts()->with(['event', 'event.project', 'event.room'])->orderBy('start', 'ASC')->get(),


        ]);
    }

    /**
     * @param User $user
     * @return Response|ResponseFactory
     */
    public function editUserTerms(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserTermsPage', [
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'terms',
        ]);
    }

    /**
     * @param User $user
     * @return Response|ResponseFactory
     */
    public function editUserPermissions(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserPermissionsPage', [
            'user_to_edit' => new UserShowResource($user),
            'available_roles' => Role::all(),
            "all_permissions" => Permission::all()->groupBy('group'),
            'currentTab' => 'permissions',
        ]);
    }

    /**
     * @param User $user
     * @return Response|ResponseFactory
     */
    public function editUserWorkProfile(User $user): Response|ResponseFactory
    {
        return inertia(
            'Users/UserWorkProfilePage',
            [
                'userToEdit' => new UserWorkProfileResource($user),
                'currentTab' => 'workProfile',
            ]
        );
    }

    /**
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function updateUserPhoto(User $user, Request $request): void
    {
        if (isset($request['photo'])) {
            $user->updateProfilePhoto($request['photo']);
        }
    }

    /**
     * @param User $user
     * @param $month
     * @return array
     */
    function getAvailabilityData(User $user, $month = null): array
    {
        $vacationDays = $user->vacations()->orderBy('from', 'ASC')->get();

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
            foreach ($vacationDays as $vacationDay){
                $vacationStart = Carbon::parse($vacationDay->from);
                $vacationEnd = Carbon::parse($vacationDay->until);
                // TODO: Check Performance
                /*if($currentDate < $vacationStart){
                    $onVacation = false;
                    continue;
                }*/
                if($vacationStart <= $currentDate && $vacationEnd >= $currentDate){
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
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $user->update($request->only('first_name','last_name', 'phone_number', 'position', 'description','email'));

        $user->departments()->sync(
            collect($request->departments)
                ->map(function ($department) {

                    $this->authorize('update', Department::find($department['id']));

                    return $department['id'];
                })
        );

        $user->syncPermissions($request->permissions);
        $user->syncRoles($request->roles);

        return Redirect::back()->with('success', 'Benutzer aktualisiert');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_checklist_status(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $user->update([
            'opened_checklists' => $request->opened_checklists
        ]);

        return Redirect::back()->with('success', 'Checklist status updated');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_area_status(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $user->update([
            'opened_areas' => $request->opened_areas
        ]);

        return Redirect::back()->with('success', 'Area status updated');
    }

    /**
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_user_can_master(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'can_master' => $request->can_master
        ]);

        return Redirect::back()->with('success', 'User updated');
    }

    /**
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_user_can_work_shifts(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'can_work_shifts' => $request->can_work_shifts
        ]);

        return Redirect::back()->with('success', 'User updated');
    }

    /**
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function update_work_data(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'work_name' => $request->work_name,
            'work_description' => $request->work_description
        ]);

        return Redirect::back()->with('success', 'User updated');
    }

    /**
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateUserCraftSettings(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'can_work_shifts' => $request->boolean('canBeAssignedToShifts'),
            'can_master' => $request->boolean('canBeUsedAsMasterCraftsman')
        ]);

        return Redirect::back();
    }

    /**
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function assignCraft(User $user, Request $request): RedirectResponse
    {
        $user->assigned_crafts()->attach(Craft::find($request->get('craftId')));

        return Redirect::back()->with('success', ['craft' => 'Gewerk erfolgreich zugeordnet.']);
    }

    /**
     * @param User $user
     * @param Craft $craft
     * @return RedirectResponse
     */
    public function removeCraft(User $user, Craft $craft): RedirectResponse
    {
        $user->assigned_crafts()->detach($craft);

        return Redirect::back()->with('success', ['craft' => 'Gewerk erfolgreich entfernt.']);
    }

    /**
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateWorkProfile(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'work_name' => $request->get('workName'),
            'work_description' => $request->get('workDescription')
        ]);

        return Redirect::back()->with('success', ['workProfile' => 'Arbeitsprofil erfolgreich aktualisiert']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return RedirectResponse
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        broadcast(new UserUpdated())->toOthers();

        return Redirect::route('users')->with('success', 'Benutzer gelöscht');
    }

    /**
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function temporaryUserUpdate(User $user, Request $request): void
    {
        $user->update($request->only([
            'temporary',
            'employStart',
            'employEnd'
        ]));
    }

    /**
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function updateUserTerms(User $user, Request $request): void
    {
        $user->update($request->only([
            'can_master',
            'weekly_working_hours',
            'salary_per_hour',
            'salary_description',
        ]));

    }

    /**
     * @param User $user
     * @param Request $request
     * @return void
     */
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
}

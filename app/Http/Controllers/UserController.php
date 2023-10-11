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

    public function search(SearchRequest $request) {

       $this->authorize('viewAny',User::class);

        return UserIndexResource::collection(User::search($request->input('query'))->get())->resolve();
    }

    public function money_source_search(SearchRequest $request) {

        //$this->authorize('viewAny',User::class);
        $wantedUserArray = [];

        $wantedUsers = User::search($request->input('query'))->get();
        foreach ($wantedUsers as $user){
            $wantedUserArray[] = $user;
        }
        return $wantedUserArray;
    }

    public function reset_user_password(Request $request) {

        $this->authorize('update',User::class);

        $request->validate([Fortify::email() => 'required|email']);

        $status = Password::broker()->sendResetLink(
            $request->only(Fortify::email())
        );

        return $status == Password::RESET_LINK_SENT
            ? Redirect::back()->with('status', __('passwords.sent_to_user', ['email' => $request->email]))
            : app(FailedPasswordResetLinkRequestResponse::class, ['status' => $status]);
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

    public function editUserShiftplan(User $user): Response|ResponseFactory
    {

        $shiftPlan = new CalendarController();
        $showCalendar = $shiftPlan->createCalendarDataForUserShiftPlan($user);
        $availabilityData = $this->getAvailabilityData($user, request('month'));

        if(\request('startDate') && \request('endDate')){

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
            ->get();

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

    public function updateUserPhoto( User $user,Request $request): void
    {
        if (isset($request['photo'])) {
            $user->updateProfilePhoto($request['photo']);
        }
    }

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

    public function update_checklist_status(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $user->update([
            'opened_checklists' => $request->opened_checklists
        ]);

        return Redirect::back()->with('success', 'Checklist status updated');
    }

    public function update_area_status(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $user->update([
            'opened_areas' => $request->opened_areas
        ]);

        return Redirect::back()->with('success', 'Area status updated');
    }

    public function update_user_can_master(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'can_master' => $request->can_master
        ]);

        return Redirect::back()->with('success', 'User updated');
    }

    public function update_user_can_work_shifts(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'can_work_shifts' => $request->can_work_shifts
        ]);

        return Redirect::back()->with('success', 'User updated');
    }

    public function update_work_data(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'work_name' => $request->work_name,
            'work_description' => $request->work_description
        ]);

        return Redirect::back()->with('success', 'User updated');
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

    public function temporaryUserUpdate(User $user, Request $request){
        $user->update($request->only([
            'temporary',
            'employStart',
            'employEnd'
        ]));
    }

    public function updateUserTerms(User $user, Request $request){
        $user->update($request->only([
            'can_master',
            'weekly_working_hours',
            'salary_per_hour',
            'salary_description',
        ]));

    }

    public function updateCalendarSettings(User $user, Request $request){
        $user->calendar_settings()->update($request->only([
            'project_status',
            'options',
            'project_management',
            'repeating_events',
            'work_shifts'
        ]));
    }
}

<?php

namespace Artwork\Modules\User\Http\Controllers;

use Antonrom\ModelChangesHistory\Models\Change;
use App\Http\Controllers\Controller;
use Artwork\Core\Http\Requests\SearchRequest;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SubEvent;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Services\PermissionPresetService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Enums\ShiftTabSort;
use Artwork\Modules\Shift\Http\Requests\UpdateUserShiftQualificationRequest;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Repositories\ShiftQualificationRepository;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Services\UserShiftQualificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\User\Enums\MemberSortEnum;
use Artwork\Modules\User\Enums\UserSortEnum;
use Artwork\Modules\User\Events\UserUpdated;
use Artwork\Modules\User\Http\Requests\MembersManagementRequest;
use Artwork\Modules\User\Http\Resources\MinimalUserIndexResource;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\User\Http\Resources\UserWorkProfileResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTimePattern;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\UserUserManagementSettingService;
use Artwork\Modules\WorkTime\Models\WorkTimeBooking;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\Permission\Models\Role;
use Throwable;
use App\Models\User as LaravelUser;

class UserController extends Controller
{
    public function __construct(
        protected AuthManager $auth,
        protected GlobalQualificationService $qualificationService,
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


    public function scoutSearch(Request $request, UserService $userService): JsonResponse
    {
        $users = [];
        if (
            request()->has('user_search') &&
            request()->get('user_search') !== null &&
            request()->get('user_search') !== ''
        ) {
            $users = $userService->searchUsers($request->string('user_search'));
        }

        return \response()->json($users);
    }

    /**
     * @param SearchRequest $request
     * @return User[]
     */
    public function moneySourceSearch(SearchRequest $request): array
    {
        $wantedUserArray = [];

        $wantedUsers = User::search($request->input('query'))
            ->query(function ($query): void {
                $query->where('email', '!=', config('artwork.deleted_user_email', 'deleted-user@artwork.local'));
            })
            ->get();
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

    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function index(
        PermissionPresetService $permissionPresetService,
        UserUserManagementSettingService $userUserManagementSettingService,
        UserService $userService,
        MembersManagementRequest $request
    ): Response|ResponseFactory {
        $saveFilterAndSort = $request->boolean('saveFilterAndSort');
        $userUserManagementSetting = $userUserManagementSettingService
            ->getFromUser($userService->getAuthUser())
            ->getAttribute('settings');
        $sortEnum = $saveFilterAndSort ?
            $request->enum('sort', UserSortEnum::class) :
            (
            $userUserManagementSetting['sort_by'] ?
                UserSortEnum::from($userUserManagementSetting['sort_by']) :
                null
            );
        $searchQuery = $request->get('query');

        $users = MinimalUserIndexResource::collection(
            !empty($searchQuery)
                ? User::search($searchQuery)
                ->query(function ($query) use ($sortEnum): void {
                    $query->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo']);
                    // Exclude the placeholder "Deleted user"
                    $query->where('email', '!=', config('artwork.deleted_user_email', 'deleted-user@artwork.local'));

                    // Sortierung nur anwenden, wenn $sortEnum vorhanden ist
                    if (!is_null($sortEnum)) {
                        switch ($sortEnum) {
                            case UserSortEnum::ALPHABETICALLY_ASCENDING:
                            case UserSortEnum::ALPHABETICALLY_DESCENDING:
                                $columns = $sortEnum->mapToColumn();
                                $dir = $sortEnum->mapToDirection();
                                $query->orderBy($columns[0], $dir);
                                $query->orderBy($columns[1], $dir);
                                break;
                            case UserSortEnum::CHRONOLOGICALLY_ASCENDING:
                            case UserSortEnum::CHRONOLOGICALLY_DESCENDING:
                                $query->orderBy($sortEnum->mapToColumn(), $sortEnum->mapToDirection());
                                break;
                        }
                    }
                })
                ->get()
                : User::query()
                ->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo'])
                // Exclude the placeholder "Deleted user"
                ->where('email', '!=', config('artwork.deleted_user_email', 'deleted-user@artwork.local'))
                ->when(!is_null($sortEnum), function ($query) use ($sortEnum): void {
                    switch ($sortEnum) {
                        case UserSortEnum::ALPHABETICALLY_ASCENDING:
                        case UserSortEnum::ALPHABETICALLY_DESCENDING:
                            $columns = $sortEnum->mapToColumn();
                            $dir = $sortEnum->mapToDirection();
                            $query->orderBy($columns[0], $dir);
                            $query->orderBy($columns[1], $dir);
                            break;
                        case UserSortEnum::CHRONOLOGICALLY_ASCENDING:
                        case UserSortEnum::CHRONOLOGICALLY_DESCENDING:
                            $query->orderBy($sortEnum->mapToColumn(), $sortEnum->mapToDirection());
                            break;
                    }
                })
                ->get()
        )->resolve();


        if ($saveFilterAndSort) {
            $userUserManagementSettingService->updateOrCreateIfNecessary(
                $userService->getAuthUser(),
                [
                    'sort_by' => $sortEnum?->name,
                ]
            );
        }

        return inertia('Users/Index', [
            'users' => $users,
            'all_permissions' => Permission::all()->groupBy('group'),
            'departments' => Department::all(),
            'roles' => Role::all(),
            'freelancers' => Freelancer::all(),
            'serviceProviders' => ServiceProvider::query()->without('contacts')->get(),
            'permission_presets' => $permissionPresetService->getPermissionPresets(),
            'invitedUsers' => Invitation::all(),
            'userSortEnumNames' => array_map(
                static function (UserSortEnum $enum): string {
                    return $enum->name;
                },
                UserSortEnum::cases()
            ),
            'userUserManagementSetting' => $userUserManagementSettingService
                ->getFromUser($userService->getAuthUser())
                ->getAttribute('settings')
        ]);
    }

    //@todo: fix phpcs error - refactor function because complexity exceeds allowed maximum
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function getAddresses(
        UserUserManagementSettingService $userUserManagementSettingService,
        UserService $userService,
        MembersManagementRequest $request
    ): Response|ResponseFactory {
        $saveFilterAndSort = $request->boolean('saveFilterAndSort');
        $userUserManagementSetting = $userUserManagementSettingService
            ->getFromUser($userService->getAuthUser())
            ->getAttribute('settings');
        $sortEnum = $saveFilterAndSort ? $request->enum('sort', MemberSortEnum::class) :
            ($userUserManagementSetting['sort_by'] ?
                UserSortEnum::from($userUserManagementSetting['sort_by']) : null);

        $freelancers = Freelancer::query()->when(
            strlen($search = $request->string('query')) > 0,
            function (Builder $builder) use ($search): void {
                $builder->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%');
            }
        )->when(
            !is_null($sortEnum),
            function (Builder $builder) use ($sortEnum): void {
                switch ($sortEnum) {
                    case MemberSortEnum::ALPHABETICALLY_ASCENDING:
                    case MemberSortEnum::ALPHABETICALLY_DESCENDING:
                        $columns = $sortEnum->mapToColumn(1);
                        $dir = $sortEnum->mapToDirection();
                        $builder->orderBy($columns[0], $dir);
                        $builder->orderBy($columns[1], $dir);
                        break;
                    case MemberSortEnum::CHRONOLOGICALLY_ASCENDING:
                    case MemberSortEnum::CHRONOLOGICALLY_DESCENDING:
                        $builder->orderBy($sortEnum->mapToColumn(1), $sortEnum->mapToDirection());
                        break;
                }
            }
        )->get();

        $serviceProviders = ServiceProvider::query()->without(['contacts'])->when(
            strlen($search = $request->string('query')) > 0,
            function (Builder $builder) use ($search): void {
                $builder->where('provider_name', 'like', '%' . $search . '%');
            }
        )->when(
            !is_null($sortEnum),
            function (Builder $builder) use ($sortEnum): void {
                switch ($sortEnum) {
                    case MemberSortEnum::ALPHABETICALLY_ASCENDING:
                    case MemberSortEnum::ALPHABETICALLY_DESCENDING:
                    case MemberSortEnum::CHRONOLOGICALLY_ASCENDING:
                    case MemberSortEnum::CHRONOLOGICALLY_DESCENDING:
                        $builder->orderBy($sortEnum->mapToColumn(2), $sortEnum->mapToDirection());
                        break;
                }
            }
        )->get();

        if ($saveFilterAndSort) {
            $userUserManagementSettingService->updateOrCreateIfNecessary(
                $userService->getAuthUser(),
                [
                    'sort_by' => $sortEnum?->name,
                ]
            );
        }

        return inertia('Users/Addresses', [
            'freelancers' => $freelancers,
            'serviceProviders' => $serviceProviders,
            'memberSortEnums' => array_map(
                function (MemberSortEnum $enum): string {
                    return $enum->name;
                },
                MemberSortEnum::cases()
            ),
            'userUserManagementSetting' => $userUserManagementSettingService
                ->getFromUser($userService->getAuthUser())
                ->getAttribute('settings')
        ]);
    }

    public function editUserInfo(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserInfoPage', [
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'info',
            "departments" => Department::all(),
            "password_reset_status" => session('status'),
            'calendar_settings' => $user->calendar_settings,
        ]);
    }

    public function editUserWorkTime(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserWorkTimePatternPage', [
            'userToEdit' => new UserShowResource($user),
            'currentTab' => 'workTimePattern',
            'workTimes' => $user->load('workTimes')->workTimes,
            'currentWorkTime' => $user->getCurrentWorkTime(),
            'nextWorkTime' => $user->getNextWorkTime(),
            'workTimePatterns' => UserWorkTimePattern::all(),
        ]);
    }

    public function editUserContract(User $user): Response|ResponseFactory
    {
        $user->load('contract');

        // Provide a default contract object if the user doesn't have a contract
        $contract = $user->contract ?? new UserContractAssign([
            'user_id' => $user->id,
            'user_contract_id' => null,
            'free_full_days_per_week' => 0,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => false,
            'compensation_period' => 0,
            'free_sundays_per_season' => 0,
            'days_off_first_26_weeks' => 0.00
        ]);

        return inertia('Users/UserContract', [
            'userToEdit' => new UserShowResource($user),
            'currentTab' => 'workTimePattern',
            'contract' => $contract,
            'userContracts' => UserContract::all(),
        ]);
    }

    public function showUserWorkTimes(User $user): Response|ResponseFactory
    {
        $startInput = request()->input('start');
        $endInput = request()->input('end');

        $start = $startInput ? Carbon::parse($startInput) : Carbon::now()->startOfMonth();
        $end = $endInput ? Carbon::parse($endInput) : Carbon::now()->endOfMonth();

        $workTimes = $this->getPlannedWorkSchedule($start, $end, $user);


        // Flach durch alle Tage iterieren
        $flatDays = collect($workTimes)->flatten(1);


        $totalWorkedMinutes = $flatDays->sum('worked_hours');
        $totalWantedMinutes = $flatDays->sum('wantedHours');

        return inertia('Users/UserWorkTimes', [
            'userToEdit' => new UserShowResource($user),
            'workTimes' => $workTimes,
            'dateRange' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'totals' => [
                'worked' => $this->convertMinutesToHoursAndMinutes($totalWorkedMinutes),
                'wanted' => $this->convertMinutesToHoursAndMinutes($totalWantedMinutes, true),
            ]
        ]);
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     * @param User $user
     * @return array<string, array<string, mixed>>
     */
    private function getPlannedWorkSchedule(Carbon $start, Carbon $end, User $user): array
    {
        $schedule = [];

        $bookings = $user->workTimeBookings()
            ->with('booker')
            ->whereBetween('booking_day', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn($b) => $b->booking_day->toDateString());

        $individualTimes = $user->individualTimes()
            ->individualByDateRange($start->toDateString(), $end->toDateString())
            ->get()
            ->flatMap(fn($t) => collect($t->days_of_individual_time)->mapWithKeys(
                fn($day) => [$day => $t->working_time_minutes ?? 0]
            ));

        $current = $start->copy();

        while ($current->lte($end)) {
            $dateKey = $current->toDateString();
            $weekday = strtolower($current->format('l'));
            $weekKey = "KW" . $current->isoWeek();

            $userWorkTime = $user->workTimes()
                ->where(function ($q) use ($current): void {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $current);
                })
                ->where(function ($q) use ($current): void {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $current);
                })
                ->orderByDesc('valid_from')
                ->first();

            $patternTime = $userWorkTime?->{$weekday};
            $dailyTargetMinutes = $patternTime
                ? Carbon::parse($patternTime)->diffInMinutes(Carbon::parse($patternTime)->copy()->startOfDay())
                : 0;

            $workedMinutes = 0;
            $nightlyMinutes = 0;
            $balanceChange = 0;
            $comments = [];
            $isSpecialDay = false;

            if (isset($bookings[$dateKey])) {
                foreach ($bookings[$dateKey] as $booking) {
                    $workedMinutes += $booking->worked_hours;
                    $nightlyMinutes += $booking->nightly_working_hours;
                    $balanceChange += $booking->work_time_balance_change;
                    $isSpecialDay = $isSpecialDay || $booking->is_special_day;

                    if ($booking->comment) {
                        $comments[] = [
                            'text' => $booking->comment,
                            'user' => $booking->booker,
                            'date' => $booking->created_at->locale(session('locale', config('app.fallback_locale')))
                                ->isoFormat('D. MMMM YYYY'),
                            'work_time_change' => $this->convertMinutesToHoursAndMinutes(
                                $booking->work_time_balance_change
                            ),
                        ];
                    }
                }
            } else {
                // Wenn kein Booking, dann prüfen ob Krankheit
                $vacation = $user->vacations()->byDate($current)->first();
                if ($vacation && $vacation->type === 'NOT_AVAILABLE') {
                    $plannedShiftMinutes = $this->getPlannedShiftMinutesForDay($user, $current);

                    if ($plannedShiftMinutes > $dailyTargetMinutes) {
                        $workedMinutes = $plannedShiftMinutes;
                        $balanceChange = $plannedShiftMinutes - $dailyTargetMinutes;
                    } else {
                        $workedMinutes = 0;
                        $balanceChange = 0;
                    }

                    $nightlyMinutes = 0; // Krankheit zählt keine Nachtzeit
                } else {
                    $workedMinutes += $this->getPlannedShiftMinutesForDay($user, $current);

                    if ($individualTimes->has($dateKey)) {
                        $workedMinutes += $individualTimes[$dateKey];
                    }
                }
            }

            $entry = [
                'weekday' => $weekday,
                'date' => $dateKey,
                'formatted_date' => $current->locale(session('locale', config('app.fallback_locale')))
                    ->isoFormat('dddd, D. MMMM YYYY'),
                'planned_minutes' => $workedMinutes,
                'planned_hours' => $this->convertMinutesToHoursAndMinutes($workedMinutes, true),
                'daily_target_minutes' => $dailyTargetMinutes,
                'daily_target_hours' => $this->convertMinutesToHoursAndMinutes($dailyTargetMinutes, true),
                'wantedHours' => $dailyTargetMinutes,
                'worked_hours' => $workedMinutes,
                'nightly_working_hours' => $nightlyMinutes,
                'work_time_balance_change' => $balanceChange,
                'is_special_day' => $isSpecialDay,
                'comments' => $comments,
                'wantedHoursFormatted' => $this->convertMinutesToHoursAndMinutes($dailyTargetMinutes, true),
                'worked_hours_formatted' => $this->convertMinutesToHoursAndMinutes($workedMinutes),
                'nightly_working_hours_formatted' => $this->convertMinutesToHoursAndMinutes($nightlyMinutes),
                'work_time_balance_change_formatted' => $this->convertMinutesToHoursAndMinutes($balanceChange),
            ];

            $schedule[$weekKey][$dateKey] = $entry;
            $current->addDay();
        }

        return $schedule;
    }


    private function getPlannedShiftMinutesForDay(User $user, Carbon $day): int
    {
        $total = 0;

        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay()->addMillisecond();

        foreach ($user->shifts as $shift) {
            $pivot = $shift->pivot;

            $shiftStart = Carbon::parse($pivot->start_date)->setTimeFrom(Carbon::parse($pivot->start_time));
            $shiftEnd = Carbon::parse($pivot->end_date)->setTimeFrom(Carbon::parse($pivot->end_time));

            // Nur Schichten berücksichtigen, die an diesem Tag aktiv sind
            if ($shiftStart->gt($dayEnd) || $shiftEnd->lt($dayStart)) {
                continue;
            }

            $breakMinutes = (int)($shift->break_minutes ?? 0);

            $workStart = max($shiftStart, $dayStart);
            $workEnd = min($shiftEnd, $dayEnd);

            if ($workStart->lt($workEnd)) {
                $duration = $workStart->diffInMinutes($workEnd) - $breakMinutes;
                $total += max(0, $duration);
            }
        }

        return (int)round($total);
    }


    private function convertMinutesToHoursAndMinutes(int $inputMinutes, bool $forcePositive = false): string
    {
        if ($forcePositive) {
            $inputMinutes = abs($inputMinutes);
        }

        $hours = floor($inputMinutes / 60);
        $minutes = abs($inputMinutes % 60); // wichtig: immer positiv anzeigen

        $sign = (!$forcePositive && $inputMinutes < 0) ? '-' : '';

        return sprintf('%s%02d:%02d', $sign, abs($hours), $minutes);
    }


    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function editUserShiftPlan(
        Request $request,
        User $user,
        UserService $userService,
        ShiftQualificationService $shiftQualificationService,
        CalendarService $calendarService,
        EventService $eventService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        SessionManager $sessionManager,
        Repository $config
    ): Response|ResponseFactory {
        $showVacationsAndAvailabilities = $request->get('showVacationsAndAvailabilities');
        $vacationMonth = $request->get('vacationMonth');
        $selectedDate = $showVacationsAndAvailabilities ?
            Carbon::parse($showVacationsAndAvailabilities) :
            Carbon::today();
        $selectedPeriodDate = $vacationMonth ?
            Carbon::parse($vacationMonth) :
            Carbon::today();

        $userService->shareCalendarAbo('shiftCalendar');

        $selectedPeriodDate->locale($sessionManager->get('locale') ?? $config->get('app.fallback_locale'));

        return Inertia::render(
            'Users/UserShiftPlanPage',
            $userService->getUserShiftPlanPageDto(
                $user,
                $calendarService,
                $eventService,
                $roomService,
                $eventTypeService,
                $projectService,
                $shiftQualificationService,
                $selectedPeriodDate,
                $selectedDate,
                $request->get('month'),
                $vacationMonth
            )
        );
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
        ShiftQualificationRepository $shiftQualificationRepository,
        CraftService $craftService
    ): Response|ResponseFactory {

        $globalQualifications = $this->qualificationService->getAll()->map(function ($qualification) use ($user) {
            return [
                'id' => $qualification->id,
                'name' => $qualification->name,
                'icon' => $qualification->icon,
                'assigned' => $user->globalQualifications->contains('id', $qualification->id),
            ];
        });

        return inertia(
            'Users/UserWorkProfilePage',
            [
                'userToEdit' => (new UserWorkProfileResource(
                    $user,
                    $craftService->getAll()
                ))->resolve(),
                'currentTab' => 'workProfile',
                'shiftQualifications' => $shiftQualificationRepository->getAllAvailableOrderedByCreationDateAscending(),
                'globalQualifications' => $globalQualifications,
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
        if ($user->id !== Auth::user()->id && !Auth::user()->can(PermissionEnum::TEAM_UPDATE->value)) {
            abort(\Illuminate\Http\Response::HTTP_FORBIDDEN);
        }


        $user->update(
            $request->only(
                'first_name',
                'last_name',
                'phone_number',
                'position',
                'pronouns',
                'description',
                'email',
                'language',
                'email_private',
                'phone_private',
                'use_chat'
            )
        );

        $user->calendar_settings->update([
            'high_contrast' => $request->get('high_contrast')
        ]);

        if (Auth::user()->can(PermissionEnum::TEAM_UPDATE->value)) {
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

    /**
     * Speichert die Popup-Chat-Position pro Nutzer.
     */
    public function updateChatPopupSettings(Request $request, User $user): void
    {
        // gleiche Berechtigungslogik wie bei updateUserDetails
        if ($user->id !== Auth::user()->id && !Auth::user()->can(PermissionEnum::TEAM_UPDATE->value)) {
            abort(\Illuminate\Http\Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'chat_popup_position' => [
                'required',
                'in:top-left,top-right,bottom-left,bottom-right,middle-left,middle-right,top-center,bottom-center'
            ],
        ]);

        $user->update($request->only('chat_popup_position'));
    }

    public function updateUserPermissionsAndRoles(Request $request, User $user): RedirectResponse
    {
        //only add permissions which are also existing to the array which gets synced with user
        $availablePermissions = PermissionEnum::cases();
        $permissionsToGrant = [];
        foreach ($request->permissions as $permissionToGrant) {
            foreach ($availablePermissions as $availablePermission) {
                if ($availablePermission->value === $permissionToGrant) {
                    $permissionsToGrant[] = $permissionToGrant;
                }
            }
        }

        //only add roles which are also existing to the array which gets synced with user
        $availableRoles = RoleEnum::cases();
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
            'work_description' => $request->get('workDescription'),
            'is_freelancer' => $request->get('is_freelancer')
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
        \Artwork\Modules\User\Models\User $user,
        \Artwork\Modules\Shift\Models\GlobalQualification $qualification,
        \Artwork\Modules\Shift\Services\GlobalQualificationService $qualificationService
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('updateWorkProfile', \Artwork\Modules\User\Models\User::class);
        $qualificationService->activateOrDeactivateInQualifiable($qualification, $user);
        return \Illuminate\Support\Facades\Redirect::back();
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

        // check if craft is already assigned
        if ($user->assignedCrafts()->where('craft_id', $craftToAssign->id)->exists()) {
            return Redirect::back();
        }

        $user->assignedCrafts()->attach($craftToAssign);

        return Redirect::back();
    }

    public function assignCraftsBulk(User $user, Request $request)
    {
        $this->authorize('updateWorkProfile', User::class);

        $craftIds = $request->get('craftIds', []);

        $validCraftIds = Craft::whereIn('id', $craftIds)->pluck('id')->toArray();

        // Filter out already assigned crafts
        $newCraftIds = array_diff($validCraftIds, $user->assignedCrafts()->pluck('craft_id')->toArray());

        if (!empty($newCraftIds)) {
            $user->assignedCrafts()->attach($newCraftIds);
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

    public function destroy(
        User $user,
        RoomService $roomService,
        EventService $eventService,
        UserService $userService,
    ): RedirectResponse {
        // Prevent self-deletion to avoid authentication/session inconsistencies
        $authUserId = null;
        try {
            $authUserId = $userService->getAuthUserId();
        } catch (\Throwable $e) {
            $authUserId = null;
        }
        if ($authUserId !== null && $authUserId === $user->id) {
            return Redirect::back()->withErrors([
                'user' => __('You cannot delete your own account.')
            ]);
        }

        // Use a dedicated placeholder user for all mandatory FK reassignments
        $reassignUserId = $this->getOrCreateDeletedPlaceholderUserId();

        // Disallow deleting the placeholder itself
        if ($user->id === $reassignUserId) {
            return Redirect::back()->withErrors([
                'user' => __('The placeholder user cannot be deleted.')
            ]);
        }

        // Handle belongsToMany relationships - detach the user
        DB::beginTransaction();
        try {
            $user->departments()->detach();
            $user->projects()->detach();
            $user->adminRooms()->detach();
            $user->crafts()->detach();
            $user->assignedCrafts()->detach();
            $user->managingCrafts()->detach();
            $user->shiftQualifications()->detach();
            $user->chats()->detach();
            $user->verifiableEventTypes()->detach();
            $user->accessMoneySources()->detach();
            // Reassign all shift_user entries to the placeholder user to satisfy FK constraints and preserve data
            try {
                ShiftUser::withTrashed()
                    ->where('user_id', $user->id)
                    ->update(['user_id' => $reassignUserId, 'deleted_at' => null]);
            } catch (\Throwable $e) {
                if (function_exists('report')) {
                    report($e);
                }
                // Fallback: ensure no blocking FK remains
                try {
                    ShiftUser::withTrashed()->where('user_id', $user->id)->forceDelete();
                } catch (\Throwable $e2) {
                    if (function_exists('report')) {
                        report($e2);
                    }
                }
            }

            // Handle hasMany relationships - reassign or delete
            // Reassign created rooms to replacement user
            $user->createdRooms()->withTrashed()->each(
                fn(Room $room) => $roomService->update(
                    $room,
                    ['user_id' => $reassignUserId]
                )
            );

            // Reassign events to replacement user
            $user->events()->withTrashed()->each(
                fn(Event $event) => $eventService->update(
                    $event,
                    ['user_id' => $reassignUserId]
                )
            );

            // Reassign shifts committed by this user, if applicable
            try {
                if (Schema::hasColumn('shifts', 'committing_user_id')) {
                    Shift::where('committing_user_id', $user->id)->update(['committing_user_id' => $reassignUserId]);
                }
            } catch (\Throwable $e) {
                if (function_exists('report')) {
                    report($e);
                }
            }

            // Delete or reassign other hasMany relationships
            $user->notificationSettings()->delete();
            $user->comments()->update(['user_id' => $reassignUserId]);
            $user->private_checklists()->update(['user_id' => $reassignUserId]);
            $user->doneTasks()->update(['user_id' => $reassignUserId]);
            // Some installations may not have a user_id column on project_files.
            // In that case, attempting to update the relation would throw a SQL error.
            // We first check the schema and only attempt an update if the column exists.
            try {
                if (Schema::hasColumn('project_files', 'user_id')) {
                    $user->project_files()->update(['user_id' => $reassignUserId]);
                }
            } catch (\Throwable $e) {
                // Log at a low level and continue without failing the whole request
                if (function_exists('report')) {
                    report($e);
                }
            }
            $user->globalNotification()->delete();
            $user->money_sources()->update(['creator_id' => $reassignUserId]);
            $user->tasks()->update(['user_id' => $reassignUserId]);
            Project::where('user_id', $user->id)->update(['user_id' => $reassignUserId]);
            $user->eventVerifications()->delete();
            $user->workTimeBookings()->delete();
            $user->productBasket()->delete();

            // Handle hasOne relationships - delete
            if ($user->calendarAbo) {
                $user->calendarAbo->delete();
            }

            if ($user->shiftCalendarAbo) {
                $user->shiftCalendarAbo->delete();
            }

            if ($user->calendar_settings) {
                $user->calendar_settings->delete();
            }

            if ($user->calendar_filter) {
                $user->calendar_filter->delete();
            }

            if ($user->shift_calendar_filter) {
                $user->shift_calendar_filter->delete();
            }

            if ($user->commentedBudgetItemsSetting) {
                $user->commentedBudgetItemsSetting->delete();
            }

            if ($user->workerShiftPlanFilter) {
                $user->workerShiftPlanFilter->delete();
            }

            if ($user->inventoryArticlePlanFilter) {
                $user->inventoryArticlePlanFilter->delete();
            }

            if ($user->inventoryManagementFilter) {
                $user->inventoryManagementFilter->delete();
            }

            if ($user->projectFilterAndSortSetting) {
                $user->projectFilterAndSortSetting->delete();
            }

            if ($user->userFilterAndSortSetting) {
                $user->userFilterAndSortSetting->delete();
            }

            if ($user->contract) {
                $user->contract->delete();
            }
            Change::query()
                ->where(function ($query) use ($user): void {
                    $query->where('changer_id', $user->id)
                        ->orWhere('changes', 'LIKE', '"changed_by": {"id": ' . $user->id . '%');
                })
                ->whereIn('changer_type', [User::class, LaravelUser::class])
                ->each(function ($change) use ($user, $reassignUserId): void {
                    $change->changer_id = $reassignUserId;
                    $change->changes = str_replace(
                        ' "changed_by": {"id": ' . $user->id,
                        ' "changed_by": {"id": ' . $reassignUserId,
                        $change->changes
                    );
                    $change->save();
                });
            SubEvent::where('user_id', $user->id)->update(['user_id' => $reassignUserId]);
            // Now delete the user
            $user->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

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
            'project_artists',
            'options',
            'project_management',
            'repeating_events',
            'work_shifts',
            'description',
            'event_name',
            'high_contrast',
            'expand_days',
            'use_event_status_color',
            'show_qualifications',
            'shift_notes',
            'hide_unoccupied_rooms',
            'display_project_groups',
            'show_unplanned_events',
            'show_planned_events',
            'hide_unoccupied_days',
            'show_shift_group_tag'
        ]));
    }

    public function toggleUserShiftTimePreset(Request $request): void
    {
        /** @var User $user */
        $user = $this->auth->user();
        $user->update([
            'is_time_preset_open' => $request->boolean('is_time_preset_open')
        ]);
    }

    public function updateSidebar(User $user, Request $request): void
    {
        $user->update($request->only([
            'is_sidebar_opened'
        ]));
    }

    public function updateChecklistStyle(User $user, Request $request): void
    {
        $user->update($request->only([
            'checklist_style'
        ]));
    }

    public function updateZoomFactor(User $user, Request $request): void
    {
        $user->update($request->only('zoom_factor'));
    }

    public function updateAtAGlance(User $user, Request $request): void
    {
        $user->update($request->only('at_a_glance'));
    }

    public function updateBulkSortId(User $user, Request $request): void
    {
        $user->update($request->only('bulk_sort_id'));
    }

    public function updateDailyView(User $user, Request $request): void
    {
        $user->update($request->only('daily_view'));
    }

    public function updateBulkColumnSize(User $user, Request $request): void
    {
        $user->update($request->only('bulk_column_size'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function operationPlan(
        Request $request,
        User $user,
        UserService $userService,
        ShiftQualificationService $shiftQualificationService,
        CalendarService $calendarService,
        EventService $eventService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        SessionManager $sessionManager,
        Repository $config
    ): Response|ResponseFactory {
        $showVacationsAndAvailabilities = $request->get('showVacationsAndAvailabilities');
        $vacationMonth = $request->get('vacationMonth');
        $selectedDate = $showVacationsAndAvailabilities ?
            Carbon::parse($showVacationsAndAvailabilities) :
            Carbon::today();
        $selectedPeriodDate = $vacationMonth ?
            Carbon::parse($vacationMonth) :
            Carbon::today();
        $userService->shareCalendarAbo('shiftCalendar');
        $selectedPeriodDate->locale($sessionManager->get('locale') ?? $config->get('app.fallback_locale'));

        return Inertia::render(
            'Shifts/UserOperationPlan',
            $userService->getUserShiftPlanPageDto(
                $user,
                $calendarService,
                $eventService,
                $roomService,
                $eventTypeService,
                $projectService,
                $shiftQualificationService,
                $selectedPeriodDate,
                $selectedDate,
                $request->get('month'),
                $vacationMonth
            )
        );
    }

    public function compactMode(User $user, Request $request): void
    {
        $user->update($request->only('compact_mode'));
    }

    public function updateShowCrafts(User $user, Request $request): void
    {
        $user->userFilters()->updateOrCreate(
            ['filter_type' => 'shift_filter'],
            [
                'craft_ids' => $this->nullableArray($request->collect('craft_ids')),
            ]
        );
    }

    /**
     * @param $collection
     * @return array<string, mixed>|null
     * @throws Throwable
     */
    private function nullableArray($collection): ?array
    {
        $array = $collection->filter()->all();
        return empty($array) ? null : array_values($array);
    }

    public function updateShowShiftQualifications(User $user, Request $request): void
    {
        $user->update($request->only('show_qualifications'));
    }

    public function calendarGoToStepper(User $user, Request $request): void
    {
        $user->update($request->only('goto_mode'));
    }

    /**
     * @throws Throwable
     */
    public function updateShiftPlanUserSortBy(
        User $user,
        Request $request
    ): void {
        $request->validate(
            [
                'sortBy' => [
                    'nullable',
                    Rule::enum(ShiftPlanWorkerSortEnum::class)
                ]
            ]
        );


        $user->updateOrFail([
            'shift_plan_user_sort_by_id' => $request->enum(
                'sortBy',
                ShiftPlanWorkerSortEnum::class
            )
        ]);
    }

    public function updateShiftTabUserSortBy(
        User $user,
        Request $request
    ): void {
        $request->validate(
            [
                'sortBy' => [
                    'nullable',
                    Rule::enum(ShiftTabSort::class)
                ]
            ]
        );


        $user->updateOrFail([
            'sort_type_shift_tab' => $request->enum(
                'sortBy',
                ShiftTabSort::class
            )
        ]);
    }

    public function updateUserOverviewHeight(User $user, Request $request): void
    {
        $user->update($request->only('drawer_height'));
    }

    public function updateInventorySortColumn(User $user, Request $request): void
    {
        $user->update($request->only('inventory_sort_column_id', 'inventory_sort_direction'));
    }

    public function updateChecklistFilter(User $user, Request $request): void
    {
        $user->update($request->only([
            'checklist_has_projects',
            'checklist_no_projects',
            'checklist_private_checklists',
            'checklist_no_private_checklists',
            'checklist_completed_tasks',
            'checklist_show_without_tasks'
        ]));
    }

    public function createAvatarImage($letters)
    {
        // Sicherheitsprüfung (max. 2 Buchstaben)
        $letters = strtoupper(substr($letters, 0, 2));

        // Hintergrundfarbe über Parameter oder Standardwert setzen
        $bgColor = request()?->query('bg', '#00a3ff'); // Standard: Blau
        $textColor = request()?->query('color', '#ffffff'); // Standard: Weiß

        // SVG in Blade rendern
        return response()->view('avatar', compact('letters', 'bgColor', 'textColor'))
            ->header('Content-Type', 'image/svg+xml');
        }

    private function getOrCreateDeletedPlaceholderUserId(): int
    {
        $email = config('artwork.deleted_user_email', 'deleted-user@artwork.local');

        $placeholder = User::where('email', $email)->first();
        if ($placeholder) {
            return (int) $placeholder->id;
        }

        $user = new User();
        $user->forceFill([
            'first_name' => 'Deleted',
            'last_name' => 'user',
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
            'email_verified_at' => now(),
            'language' => config('app.fallback_locale', 'en'),
            // Required JSON columns without DB defaults must be set explicitly
            'opened_checklists' => json_encode([]),
            'opened_areas' => json_encode([]),
        ]);
        $user->save();
        // Ensure the placeholder is not present in Meilisearch index
        try {
            $user->unsearchable();
        } catch (\Throwable $e) {
            // ignore indexing issues
        }

        return (int) $user->id;
    }

    /**
     * Toggle a shift qualification for a user in a specific craft (morphToMany pivot with craft_id)
     */
    public function updateCraftShiftQualification(
        User $user,
        Craft $craft,
        ShiftQualification $qualification
    ): \Illuminate\Http\RedirectResponse {
        $this->authorize('updateWorkProfile', \Artwork\Modules\User\Models\User::class);

        // morphToMany: shiftQualifications() mitPivot('craft_id')
        $pivotExists = $user->shiftQualifications()
            ->wherePivot('craft_id', $craft->id)
            ->where('shift_qualification_id', $qualification->id)->exists();
        if ($pivotExists) {
            $user->shiftQualifications()->newPivotStatement()
                ->where('qualifiable_id', $user->id)
                ->where('qualifiable_type', $user->getMorphClass())
                ->where('shift_qualification_id', $qualification->id)
                ->where('craft_id', $craft->id)
                ->delete();
        } else {
            $user->shiftQualifications()->attach($qualification->id, [
                'craft_id' => $craft->id
            ]);
        }
        return Redirect::back();
    }
}

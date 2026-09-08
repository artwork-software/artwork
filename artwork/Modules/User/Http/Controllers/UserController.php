<?php

namespace Artwork\Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Artwork\Core\Http\Requests\SearchRequest;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Enum\ShiftPlanWorkerSortEnum;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\SubEvent;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\ExternalUserManagement\Models\ExternalUserSource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Permission\Models\Permission;
use Artwork\Modules\Permission\Services\PermissionPresetService;
use Artwork\Modules\Permission\Services\PermissionCatalogPresenter;
use Artwork\Modules\Permission\Services\PermissionChangeLogService;
use Artwork\Modules\Permission\Services\PermissionImplicationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Enums\ShiftTabSort;
use Artwork\Modules\Shift\Models\CompensationDayOff;
use Artwork\Modules\Shift\Http\Requests\UpdateUserShiftQualificationRequest;
use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Repositories\ShiftQualificationRepository;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Models\UserShiftKpiSnapshot;
use Artwork\Modules\Shift\Services\ShiftKpiTrackingService;
use Artwork\Modules\Shift\Services\ShiftRuleService;
use Artwork\Modules\Shift\Services\UserShiftQualificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\User\Enums\MemberSortEnum;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Enums\UserSortEnum;
use Artwork\Modules\User\Events\UserUpdated;
use Artwork\Modules\User\Http\Requests\MembersManagementRequest;
use Artwork\Modules\User\Http\Resources\MinimalUserIndexResource;
use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\WorkTime\Models\OvertimePayout;
use Artwork\Modules\WorkTime\Models\UserOvertime;
use Artwork\Modules\WorkTime\Repositories\UserOvertimeRepository;
use Artwork\Modules\WorkTime\Services\OvertimeService;
use Artwork\Modules\User\Http\Resources\UserWorkProfileResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Models\UserContractAssign;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\User\Models\UserWorkTimePattern;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\UserUserManagementSettingService;
use Artwork\Modules\WorkTime\Services\WorkTimeCalculationService;
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

class UserController extends Controller
{
    public function __construct(
        protected AuthManager $auth,
        protected GlobalQualificationService $qualificationService,
        private readonly ShiftRuleService $shiftRuleService,
        private readonly WorkTimeCalculationService $workTimeCalculationService,
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

    /**
     * Filtert die Nutzerliste nach Authentifizierungsquelle:
     * 'sso' → alle IdP-gebundenen, oder ein konkreter Provider (local|oidc|ldap).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyAuthProviderFilter($query, ?string $filter): void
    {
        if ($filter === 'sso') {
            $query->where('auth_provider', '!=', 'local');

            return;
        }

        if (in_array($filter, ['local', 'oidc', 'ldap'], true)) {
            $query->where('auth_provider', $filter);
        }
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
        $authProviderFilter = $request->get('auth_provider_filter');

        $users = MinimalUserIndexResource::collection(
            !empty($searchQuery)
                ? User::search($searchQuery)
                ->query(function ($query) use ($sortEnum, $authProviderFilter): void {
                    $query->without(['calendar_settings', 'calendarAbo', 'shiftCalendarAbo']);
                    $query->with('departments');
                    // Exclude the placeholder "Deleted user"
                    $query->where('email', '!=', config('artwork.deleted_user_email', 'deleted-user@artwork.local'));

                    $this->applyAuthProviderFilter($query, $authProviderFilter);

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
                ->with('departments')
                // Exclude the placeholder "Deleted user"
                ->where('email', '!=', config('artwork.deleted_user_email', 'deleted-user@artwork.local'))
                ->when($authProviderFilter, fn ($query) => $this->applyAuthProviderFilter($query, $authProviderFilter))
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

        // Warn-Badge "Arbeitszeitmuster fehlt" (Personalverwaltung): eine konstante Query für die heute
        // gültigen Muster aller gelisteten Schichtarbeitenden, kein N+1. Das Flag can_work_shifts kommt
        // aus den bereits geladenen Modellen (MinimalUserIndexResource), nicht aus einer zweiten Query.
        // Nur Personen, die Schichten arbeiten, brauchen ein Muster (Soll gilt nur im Dienstplan).
        $shiftWorkerIds = array_values(array_map(
            static fn (array $listedUser): int => (int) $listedUser['id'],
            array_filter($users, static fn (array $listedUser): bool => (bool) ($listedUser['can_work_shifts'] ?? false))
        ));
        $userIdsWithPattern = $this->workTimeCalculationService->userIdsWithPatternOn($shiftWorkerIds);
        $shiftWorkerLookup = array_flip($shiftWorkerIds);
        foreach ($users as &$listedUser) {
            $listedUserId = (int) ($listedUser['id'] ?? 0);
            $listedUser['can_work_shifts'] = isset($shiftWorkerLookup[$listedUserId]);
            $listedUser['work_time_pattern_missing'] = isset($shiftWorkerLookup[$listedUserId])
                && !isset($userIdsWithPattern[$listedUserId]);
        }
        unset($listedUser);

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
            // Rollen (= Admin-Rolle) nur für Admins wählbar; Altbestand-Rollen in der DB werden nicht angeboten,
            // weil StoreInvitationRequest nur RoleEnum akzeptiert.
            'roles' => $userService->getAuthUser()->hasRole(RoleEnum::ARTWORK_ADMIN->value)
                ? Role::query()->whereIn('name', array_column(RoleEnum::cases(), 'value'))->get()
                : [],
            'freelancers' => Freelancer::all(),
            'serviceProviders' => ServiceProvider::query()->without('contacts')->get(),
            'permission_presets' => $permissionPresetService->getPermissionPresets()
                ->map(static fn ($preset): array => [
                    'id' => $preset->id,
                    'name' => $preset->name,
                    'permissions' => $preset->permissionNames(),
                ])->values(),
            'catalog' => app(PermissionCatalogPresenter::class)->present(),
            'invitedUsers' => Invitation::all(),
            'userSortEnumNames' => array_map(
                static function (UserSortEnum $enum): string {
                    return $enum->name;
                },
                UserSortEnum::cases()
            ),
            'userUserManagementSetting' => $userUserManagementSettingService
                ->getFromUser($userService->getAuthUser())
                ->getAttribute('settings'),
            'hasActiveSsoSource' => ExternalUserSource::query()->where('active', true)->exists()
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
                ->getAttribute('settings'),
            'catalog' => app(PermissionCatalogPresenter::class)->present(),
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

    public function tooltipInfo(User $user): JsonResponse
    {
        $canViewPrivate = Auth::user()->can(PermissionEnum::CAN_VIEW_PRIVATE_USER_INFO->value);

        return response()->json([
            'id' => $user->getAttribute('id'),
            'first_name' => $user->getAttribute('first_name'),
            'last_name' => $user->getAttribute('last_name'),
            'profile_photo_url' => $user->getAttribute('profile_photo_url'),
            'pronouns' => $user->getAttribute('pronouns'),
            'position' => $user->getAttribute('position'),
            'business' => $user->getAttribute('business'),
            'description' => $user->getAttribute('description'),
            'email' => !$user->getAttribute('email_private') || $canViewPrivate
                ? $user->getAttribute('email')
                : null,
            'phone_number' => !$user->getAttribute('phone_private') || $canViewPrivate
                ? $user->getAttribute('phone_number')
                : null,
            'email_private' => (bool) $user->getAttribute('email_private'),
            'phone_private' => (bool) $user->getAttribute('phone_private'),
        ]);
    }

    /**
     * Alte Route user.edit.work-time-pattern → Tab "Vertrag & Arbeitszeit".
     */
    public function editUserWorkTime(User $user): RedirectResponse
    {
        return redirect()->route('user.edit.contract-and-work-time', $user);
    }

    /**
     * Alte Route user.edit.contract → Tab "Vertrag & Arbeitszeit".
     */
    public function editUserContract(User $user): RedirectResponse
    {
        return redirect()->route('user.edit.contract-and-work-time', $user);
    }

    /**
     * Tab "Vertrag & Arbeitszeit": Vertragszeiträume (Historie, user_contract_assigns) und
     * Arbeitszeit-Sätze (user_work_times) der Person als Zeitstrahl, dazu die Vorlagen für die Modals.
     */
    public function editContractAndWorkTime(User $user): Response|ResponseFactory
    {
        $user->load(['contractAssigns.userContract', 'workTimes.workTimePattern']);

        $contractAssigns = $user->contractAssigns
            ->map(function (UserContractAssign $assign): array {
                $template = $assign->userContract;
                $data = $assign->toArray();
                $data['valid_from'] = $assign->valid_from?->toDateString();
                $data['valid_until'] = $assign->valid_until?->toDateString();
                $data['contract_name'] = $template?->name;
                $data['is_current'] = $assign->coversDate(Carbon::today());
                $data['deviations'] = $template === null
                    ? []
                    : collect(self::CONTRACT_COMPARE_FIELDS)
                        ->filter(fn (string $field): bool => self::normalizeContractValue($assign->getAttribute($field))
                            !== self::normalizeContractValue($template->getAttribute($field)))
                        ->map(fn (string $field): array => [
                            'key' => $field,
                            'value' => $assign->getAttribute($field),
                            'template_value' => $template->getAttribute($field),
                        ])
                        ->values()
                        ->all();
                unset($data['user_contract']);

                return $data;
            })
            ->values();

        $workTimes = $user->workTimes
            ->sortBy(fn (UserWorkTime $workTime): string => $workTime->valid_from?->toDateString() ?? '')
            ->map(function (UserWorkTime $workTime): array {
                $data = $workTime->toArray();
                $data['valid_from'] = $workTime->valid_from?->toDateString();
                $data['valid_until'] = $workTime->valid_until?->toDateString();
                $data['pattern_name'] = $workTime->workTimePattern?->name;
                $data['is_current'] = ($workTime->valid_from === null || !$workTime->valid_from->gt(Carbon::today()))
                    && ($workTime->valid_until === null || !$workTime->valid_until->lt(Carbon::today()));
                unset($data['work_time_pattern']);

                return $data;
            })
            ->values();

        return inertia('Users/UserContractWorkTimePage', [
            'userToEdit' => new UserShowResource($user),
            'currentTab' => 'contractAndWorkTime',
            'contractAssigns' => $contractAssigns,
            'workTimes' => $workTimes,
            'userContracts' => UserContract::all(),
            'workTimePatterns' => UserWorkTimePattern::all(),
            'today' => Carbon::today()->toDateString(),
        ]);
    }

    /** Felder, die Zuweisung und Vorlage gemeinsam haben – Abweichungen werden als Chips angezeigt. */
    private const CONTRACT_COMPARE_FIELDS = [
        'free_full_days_per_week',
        'free_half_days_per_week',
        'special_day_rule_active',
        'compensation_period',
        'overtime_rule_active',
        'overtime_compensation_period',
        'free_sundays_per_season',
        'free_sundays_per_season_active',
        'days_off_first_26_weeks',
        'days_off_first_26_weeks_active',
        'free_sundays_sat_mon_per_half',
        'free_sundays_sat_mon_per_half_active',
        'free_sundays_and_saturdays_per_season',
        'free_sundays_and_saturdays_per_season_active',
        'free_sundays_per_calendar_year',
        'free_sundays_per_calendar_year_active',
        'one_and_half_day_combinations',
        'one_and_half_day_combinations_active',
        'annual_vacation_days',
    ];

    private static function normalizeContractValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if ($value === null || $value === '') {
            return '';
        }

        return (string) (float) $value;
    }

    public function showUserWorkTimes(User $user): Response|ResponseFactory
    {
        $startInput = request()->input('start');
        $endInput = request()->input('end');

        $start = $this->parseDateOrDefault($startInput, Carbon::now()->startOfMonth());
        $end = $this->parseDateOrDefault($endInput, Carbon::now()->endOfMonth());

        // Guard against an inverted range (e.g. only one bound supplied/invalid)
        if ($end->lessThan($start)) {
            $end = $start->copy()->endOfMonth();
        }

        $workTimes = $this->getPlannedWorkSchedule($start, $end, $user);

        return inertia('Users/UserWorkTimes', [
            'userToEdit' => new UserShowResource($user),
            'workTimes' => $workTimes,
            'dateRange' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'totals' => $this->scheduleTotals($workTimes),
        ]);
    }

    /**
     * Safely parse a date input coming from the request, falling back to a default
     * when the value is missing or not a valid date (e.g. the frontend sends the
     * literal string "NaN-NaN-NaN" when a date picker holds an invalid value).
     */
    private function parseDateOrDefault(mixed $value, Carbon $default): Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return $default;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $default;
        }
    }

    public function editUserCompensationDays(User $user): Response|ResponseFactory
    {
        $compensationData = $this->shiftRuleService->getCompensationDataForUser($user);

        return inertia('Users/UserCompensationDays', array_merge(
            [
                'userToEdit' => new UserShowResource($user),
                'currentTab' => 'compensationDays',
            ],
            $compensationData
        ));
    }

    /**
     * DP-18: Lazy-Endpoints für das Info-Modal je User im Schichtplan.
     * Jeder Tab lädt seine Daten erst beim Öffnen (Performance).
     */
    public function shiftUserInfoSeason(User $user, ShiftKpiTrackingService $service): JsonResponse
    {
        $bounds = $service->getSeasonBounds();
        if ($bounds === null) {
            // Leere/ungültige Spielzeit-Einstellung -> Hinweis statt Carbon::parse('')-Absturz
            return response()->json([
                'error' => true,
                'message' => __('The playing time window is not configured. Set it under Tool settings > Communication & Legal.'),
            ], 422);
        }
        [$seasonStart, $seasonEnd] = $bounds;
        $kpis = $service->computeForUser($user, $seasonStart, $seasonEnd);

        $snapshot = UserShiftKpiSnapshot::query()
            ->where('user_id', $user->id)
            ->where('season_start', $seasonStart->toDateString())
            ->where('season_end', $seasonEnd->toDateString())
            ->first();

        return response()->json([
            'kpis' => $kpis,
            'season' => [
                'start' => $seasonStart->toDateString(),
                'end' => $seasonEnd->toDateString(),
            ],
            // Zählregel: abgeschlossene Tage der Spielzeit (bis gestern); Anzeige = aktueller Stand
            'counted_until' => Carbon::yesterday()->toDateString(),
            'snapshot_recalculated_at' => $snapshot?->recalculated_at,
            // Für das Tab-Label "Überstunden (inaktiv)" bereits beim ersten Laden verfügbar
            'overtime_rule_active' => (bool) ($user->contract?->overtime_rule_active ?? false),
        ]);
    }

    public function shiftUserInfoCompensation(User $user): JsonResponse
    {
        return response()->json($this->shiftRuleService->getCompensationDataForUser($user));
    }

    /**
     * Offene Regelverstöße (status active) der Person — read-only, ohne Bearbeitungsdaten.
     * Der Kompensations-Tab liefert nur Verstöße OHNE Ersatzfrei-Tage; hier kommen alle
     * offenen Verstöße (auch mit gewährtem Ersatzfrei), z. B. für "Meine Zahlen".
     * Formatierung von Datum/Messwert liegt im Frontend.
     */
    public function shiftUserInfoViolations(User $user): JsonResponse
    {
        $violations = ShiftRuleViolation::query()
            ->with('shiftRule:id,name,trigger_type,description,warning_color')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByDesc('violation_date')
            ->get()
            ->map(static fn (ShiftRuleViolation $violation): array => [
                'id' => $violation->id,
                'violation_date' => $violation->violation_date?->toDateString(),
                'display_name' => $violation->getDisplayName(),
                'rule_name' => $violation->shiftRule?->name,
                'title' => $violation->title,
                'trigger_type' => $violation->shiftRule?->trigger_type,
                'message' => $violation->getViolationMessage(),
                'status' => $violation->status,
                'severity' => $violation->severity,
                'warning_color' => $violation->getWarningColor(),
                'violation_data' => $violation->violation_data,
                'is_manual' => (bool) $violation->is_manual,
                'compensation_days' => $violation->compensation_days,
                'compensation_deadline' => $violation->compensation_deadline?->toDateString(),
            ])
            ->values();

        return response()->json([
            'violations' => $violations,
            'count' => $violations->count(),
        ]);
    }

    /**
     * Urlaub im Kalenderjahr INKLUSIVE geplanter Tage (Spielzeit-Tab zählt nur bis gestern).
     * Zählregel identisch zum KPI-Dienst: ganzer Tag = 1, halber Tag = 0,5.
     */
    public function shiftUserInfoVacation(User $user, ShiftKpiTrackingService $service): JsonResponse
    {
        $year = Carbon::now()->year;
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();

        $vacations = $user->vacations()
            ->where('type', 'OFF_WORK')
            ->whereBetween('date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->orderBy('date')
            ->get(['id', 'date', 'full_day', 'day_part', 'comment']);

        $granted = $service->grantedVacationUnitsForUser($user, $yearStart, $yearEnd, includePlanned: true);
        $entitlement = $service->annualVacationEntitlement($user);

        return response()->json([
            'year' => $year,
            'period' => [
                'start' => $yearStart->toDateString(),
                'end' => $yearEnd->toDateString(),
            ],
            'includes_planned' => true,
            'entitlement' => $entitlement,
            'granted' => $granted,
            'remaining' => $entitlement - $granted,
            'vacations' => $vacations,
        ]);
    }

    public function shiftUserInfoWorktimes(User $user): JsonResponse
    {
        $this->authorizeHourAccountAccess($user);
        $start = $this->parseDateOrDefault(request()->input('start'), Carbon::now()->startOfMonth())->startOfDay();
        $end = $this->parseDateOrDefault(request()->input('end'), $start->copy()->endOfMonth())->startOfDay();
        if ($end->lessThan($start)) {
            $end = $start->copy()->endOfMonth();
        }
        // Zeitraum begrenzen (Modal-Monatsnavigation): max. ein Jahr
        if ($start->diffInDays($end) > 366) {
            $end = $start->copy()->addYear();
        }

        $workTimes = $this->getPlannedWorkSchedule($start, $end, $user);

        return response()->json([
            'workTimes' => $workTimes,
            'dateRange' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'totals' => $this->scheduleTotals($workTimes),
        ]);
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $workTimes
     * @return array<string, mixed>
     */
    private function scheduleTotals(array $workTimes): array
    {
        $flatDays = collect($workTimes)->flatten(1);
        $totalWorkedMinutes = (int) $flatDays->sum('worked_hours');
        // Mindestens ein Tag ohne Arbeitszeitmuster -> Soll/Differenz des Zeitraums unbekannt (null)
        $daysWithoutPattern = $flatDays->filter(static fn (array $d): bool => !empty($d['target_unknown']))->count();
        $targetUnknown = $daysWithoutPattern > 0;
        $totalWantedMinutes = $targetUnknown ? null : (int) $flatDays->sum('wantedHours');
        $difference = $targetUnknown ? null : $totalWorkedMinutes - $totalWantedMinutes;

        return [
            'worked' => $this->convertMinutesToHoursAndMinutes($totalWorkedMinutes),
            'wanted' => $targetUnknown ? null : $this->convertMinutesToHoursAndMinutes($totalWantedMinutes, true),
            'worked_minutes' => $totalWorkedMinutes,
            'wanted_minutes' => $totalWantedMinutes,
            'difference_minutes' => $difference,
            'difference' => $targetUnknown ? null : $this->convertMinutesToHoursAndMinutes($difference),
            'difference_signed' => $targetUnknown ? null : WorkTimeCalculationService::formatSignedHours($difference),
            'target_unknown' => $targetUnknown,
            'days_without_pattern' => $daysWithoutPattern,
        ];
    }

    /**
     * Soll/Ist für den angezeigten Einsatzplan-Zeitraum (Kopfzeile "Geplant … · Soll …").
     * Soll aus dem Arbeitszeitmuster über den WorkTimeCalculationService; fehlt an mindestens
     * einem Tag ein Muster, ist das Soll unbekannt (target_unknown -> Badge "Arbeitszeitmuster fehlt").
     *
     * @param array<int, string>|null $dateValue [start, end] als Y-m-d
     * @return array<string, mixed>|null
     */
    private function operationPlanTargetSummary(User $user, ?array $dateValue): ?array
    {
        if (!is_array($dateValue) || count($dateValue) < 2) {
            return null;
        }

        try {
            $start = Carbon::parse((string) $dateValue[0])->startOfDay();
            $end = Carbon::parse((string) $dateValue[1])->startOfDay();
        } catch (Throwable) {
            return null;
        }
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $summary = WorkTimeCalculationService::summarizeRange(
            $this->workTimeCalculationService->breakdownForRange($user, $start, $end)
        );
        $unknown = $summary['target_unknown'];

        return [
            'target_minutes' => $summary['target'],
            'target_formatted' => $unknown
                ? null
                : $this->convertMinutesToHoursAndMinutes((int) $summary['target'], true),
            'actual_minutes' => $summary['actual'],
            'actual_formatted' => $this->convertMinutesToHoursAndMinutes($summary['actual']),
            'difference_minutes' => $summary['balance'],
            'difference_signed' => $unknown
                ? null
                : WorkTimeCalculationService::formatSignedHours((int) $summary['balance']),
            'target_unknown' => $unknown,
            'days_without_pattern' => $summary['days_without_pattern'],
            'days' => $summary['days'],
        ];
    }

    /**
     * DP-18 Stufe 2: Überstunden-Daten (Tab im Info-Modal + User-Detailseite).
     */
    private function buildOvertimePayload(User $user): array
    {
        $repository = app(UserOvertimeRepository::class);
        $assign = $user->contract;
        $stats = $repository->getDashboardStats($user->id);

        $entries = $repository->getForUser($user->id)
            ->map(fn (UserOvertime $e) => [
                'id' => $e->id,
                'date' => $e->date->toDateString(),
                'minutes' => $e->minutes,
                'minutes_formatted' => $this->convertMinutesToHoursAndMinutes($e->minutes),
                'remaining_minutes' => $e->remaining_minutes,
                'remaining_formatted' => $this->convertMinutesToHoursAndMinutes($e->remaining_minutes),
                'paid_out_minutes' => $e->paid_out_minutes,
                'deadline' => $e->deadline->toDateString(),
                'status' => $e->status,
                'paid_out_by' => $e->paidOutByUser
                    ? $e->paidOutByUser->first_name . ' ' . $e->paidOutByUser->last_name
                    : null,
            ])->values()->toArray();

        $payouts = OvertimePayout::query()
            ->where('user_id', $user->id)
            ->with('createdBy:id,first_name,last_name')
            ->orderByDesc('payout_date')
            ->get()
            ->map(fn (OvertimePayout $p) => [
                'id' => $p->id,
                'minutes' => $p->minutes,
                'hours_formatted' => $this->convertMinutesToHoursAndMinutes($p->minutes),
                'payout_date' => $p->payout_date->toDateString(),
                'comment' => $p->comment,
                'created_by' => $p->createdBy
                    ? $p->createdBy->first_name . ' ' . $p->createdBy->last_name
                    : null,
            ])->values()->toArray();

        return [
            'rule_active' => (bool) $assign?->overtime_rule_active,
            'compensation_period' => $assign?->overtime_compensation_period,
            'open_minutes' => $stats['open_minutes'],
            'open_formatted' => $this->convertMinutesToHoursAndMinutes($stats['open_minutes']),
            'payable_minutes' => $stats['payable_minutes'],
            'payable_formatted' => $this->convertMinutesToHoursAndMinutes($stats['payable_minutes']),
            'paid_out_minutes' => $stats['paid_out_minutes'],
            'paid_out_formatted' => $this->convertMinutesToHoursAndMinutes($stats['paid_out_minutes']),
            'entries' => $entries,
            'payouts' => $payouts,
            'can_pay_out' => auth()->user()?->can('can pay out overtime') ?? false,
        ];
    }

    public function shiftUserInfoOvertime(User $user): JsonResponse
    {
        $this->authorizeHourAccountAccess($user);

        return response()->json($this->buildOvertimePayload($user));
    }

    /**
     * Das Info-Fenster im Dienstplan umging bisher "Stundenkonten sehen" (Konzept Nutzerrechte 6.2 F):
     * fremde Arbeitszeiten/Überstunden nur mit diesem Recht oder Personalverwaltung. Admins via Gate::before.
     */
    private function authorizeHourAccountAccess(User $user): void
    {
        $viewer = Auth::user();
        abort_unless(
            $viewer->id === $user->id
            || $viewer->canAny([
                PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value,
                PermissionEnum::MA_MANAGER->value,
            ]),
            403
        );
    }

    public function editUserOvertime(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserOvertime', [
            'userToEdit' => new UserShowResource($user),
            'currentTab' => 'overtime',
            'overtime' => $this->buildOvertimePayload($user),
        ]);
    }

    public function payOutOvertime(Request $request, User $user, OvertimeService $service): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => 'required|integer|min:1',
            'comment' => 'nullable|string|max:1000',
            'payout_date' => 'nullable|date',
        ]);

        $service->payOut(
            $user,
            (int) $validated['minutes'],
            (int) auth()->id(),
            $validated['comment'] ?? null,
            !empty($validated['payout_date']) ? Carbon::parse($validated['payout_date']) : null
        );

        return response()->json($this->buildOvertimePayload($user->fresh()));
    }

    /**
     * Ist-Stunden je Tag, gruppiert nach KW. Tageswerte kommen ausschließlich aus dem
     * WorkTimeCalculationService (Soll/Ist, Sondertage, Ersatzfreie Tage, Krank/Urlaub).
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function getPlannedWorkSchedule(Carbon $start, Carbon $end, User $user): array
    {
        $schedule = [];
        $locale = session('locale', config('app.fallback_locale'));

        $bookings = $user->workTimeBookings()
            ->with('booker')
            ->whereBetween('booking_day', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($b) => $b->booking_day->toDateString());

        $compensationDayOffs = CompensationDayOff::where('user_id', $user->id)
            ->whereNotNull('granted_date')
            ->whereBetween('granted_date', [$start->toDateString(), $end->toDateString()])
            ->with(['violation:id,shift_rule_id', 'violation.shiftRule:id,name', 'grantedByUser:id,first_name,last_name'])
            ->get()
            ->groupBy(fn ($d) => $d->granted_date->toDateString());

        // Relationen für den Zeitraum gezielt laden (kein $user->shifts über alle Jahre)
        $user->setRelation(
            'shifts',
            $user->shifts()
                ->where('shifts.start_date', '<=', $end->toDateString())
                ->where('shifts.end_date', '>=', $start->toDateString())
                ->get()
        );
        $breakdowns = $this->workTimeCalculationService->breakdownForRange($user, $start, $end, [
            'holiday_comp_days' => $compensationDayOffs->flatten(1)->where('for_holiday', true),
        ]);
        $user->unsetRelation('shifts');

        $current = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($current->lte($last)) {
            $dateKey = $current->toDateString();
            $weekday = strtolower($current->format('l'));
            $weekKey = "KW" . $current->isoWeek();
            $day = $breakdowns[$dateKey];

            $compensationInfo = null;
            if (isset($compensationDayOffs[$dateKey])) {
                $compensationInfo = $compensationDayOffs[$dateKey]->map(fn ($d) => [
                    'value' => (float) $d->value,
                    'for_holiday' => (bool) $d->for_holiday,
                    'rule_name' => $d->violation?->shiftRule?->name,
                    'granted_by' => $d->grantedByUser
                        ? $d->grantedByUser->first_name . ' ' . $d->grantedByUser->last_name
                        : null,
                ])->values()->toArray();
            }

            $comments = [];
            foreach ($bookings[$dateKey] ?? [] as $booking) {
                if ($booking->comment) {
                    $comments[] = [
                        'text' => $booking->comment,
                        'user' => $booking->booker,
                        'date' => $booking->created_at->locale($locale)->isoFormat('D. MMMM YYYY'),
                        'work_time_change' => $this->convertMinutesToHoursAndMinutes(
                            $booking->work_time_balance_change
                        ),
                    ];
                }
            }

            $workedMinutes = (int) $day['actual'];
            // Kein gültiges Arbeitszeitmuster an diesem Tag -> Soll unbekannt: Soll-/Differenzfelder null,
            // daily_target_minutes bleibt 0 (Altkonsument Users/UserWorkTimes.vue rechnet damit)
            $targetUnknown = $day['target'] === null || !empty($day['target_unknown']);
            $dailyTargetMinutes = $targetUnknown ? 0 : (int) $day['target'];
            $balanceChange = $targetUnknown ? null : (int) $day['balance'];
            $nightlyMinutes = (int) $day['nightly_minutes'];

            $entry = [
                'weekday' => $weekday,
                'date' => $dateKey,
                'formatted_date' => $current->locale($locale)->isoFormat('dddd, D. MMMM YYYY'),
                'planned_minutes' => $workedMinutes,
                'planned_hours' => $this->convertMinutesToHoursAndMinutes($workedMinutes, true),
                'daily_target_minutes' => $dailyTargetMinutes,
                'daily_target_hours' => $targetUnknown
                    ? '–' // Altkonsument Users/UserWorkTimes.vue rendert den String direkt
                    : $this->convertMinutesToHoursAndMinutes($dailyTargetMinutes, true),
                'base_target_minutes' => $targetUnknown ? null : (int) $day['base_target'],
                'target_unknown' => $targetUnknown,
                'wantedHours' => $targetUnknown ? null : $dailyTargetMinutes,
                'worked_hours' => $workedMinutes,
                'nightly_working_hours' => $nightlyMinutes,
                'work_time_balance_change' => $balanceChange,
                'has_booking' => (bool) $day['has_booking'],
                'is_special_day' => (bool) $day['is_special_day'],
                'special_day_name' => $day['special_day_name'],
                'special_day_counts' => (bool) $day['special_day_counts'],
                'target_reduction' => (int) $day['target_reduction'],
                'target_reduction_formatted' => $this->convertMinutesToHoursAndMinutes((int) $day['target_reduction'], true),
                'reduction_reason' => $day['reduction_reason'],
                'reference_period' => $day['reference_period'],
                'reference_weekday_average' => $day['reference_weekday_average'],
                'reference_weekday_average_formatted' => $day['reference_weekday_average'] !== null
                    ? $this->convertMinutesToHoursAndMinutes((int) $day['reference_weekday_average'], true)
                    : null,
                'is_sick' => (bool) $day['is_sick'],
                'is_vacation' => (bool) $day['is_vacation'],
                'vacation_factor' => (float) $day['vacation_factor'],
                'is_compensation_day_off' => $compensationInfo !== null,
                'compensation_day_off_info' => $compensationInfo,
                'comments' => $comments,
                'wantedHoursFormatted' => $targetUnknown
                    ? null
                    : $this->convertMinutesToHoursAndMinutes($dailyTargetMinutes, true),
                'worked_hours_formatted' => $this->convertMinutesToHoursAndMinutes($workedMinutes),
                'nightly_working_hours_formatted' => $this->convertMinutesToHoursAndMinutes($nightlyMinutes),
                'work_time_balance_change_formatted' => $balanceChange === null
                    ? null
                    : $this->convertMinutesToHoursAndMinutes($balanceChange),
            ];

            $schedule[$weekKey][$dateKey] = $entry;
            $current->addDay();
        }

        return $schedule;
    }

    private function convertMinutesToHoursAndMinutes(int $inputMinutes, bool $forcePositive = false): string
    {
        $absMinutes = abs($inputMinutes);
        $hours = floor($absMinutes / 60);
        $minutes = $absMinutes % 60;

        $sign = (!$forcePositive && $inputMinutes < 0) ? '-' : '';

        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
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
        // Einsatzplan-Sichtregel (Kundenmeldung: Tab war über die Nutzer*innenliste
        // für alle offen): eigener Plan nur mit "can view own roster", fremde nur
        // mit Dienstplan-Sichtrechten.
        $this->authorize('viewOperationPlan', $user);

        // Deep-Link aus Benachrichtigungen (ShiftNotificationLinkService): start_date/end_date
        // in der URL bestimmen den angezeigten Zeitraum des Einsatzplans — nur für diesen Request
        // (Override im UserService), der gespeicherte Filter der eingeloggten Person bleibt unverändert.
        $this->applyOperationPlanPeriodFromRequest($request, $userService);

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

        $pageDto = $userService->getUserShiftPlanPageDto(
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
        );

        return Inertia::render(
            'Users/UserShiftPlanPage',
            array_merge($pageDto->toArray(), [
                // Soll für den angezeigten Zeitraum aus dem Arbeitszeitmuster (statt Wochenstunden/7 im Frontend)
                'workTimeTarget' => $this->operationPlanTargetSummary($user, $pageDto->getDateValue()),
            ])
        );
    }

    public function editUserTerms(User $user): Response|ResponseFactory
    {
        return inertia('Users/UserTermsPage', [
            'user_to_edit' => new UserShowResource($user),
            'currentTab' => 'terms',
        ]);
    }

    public function editUserPermissions(
        User $user,
        PermissionCatalogPresenter $catalogPresenter,
        PermissionPresetService $permissionPresetService
    ): Response|ResponseFactory {
        return inertia('Users/UserPermissionsPage', [
            'user_to_edit' => new UserShowResource($user),
            'available_roles' => Role::all(),
            'catalog' => $catalogPresenter->present($user),
            'permission_presets' => $permissionPresetService->getPermissionPresets()
                ->map(static fn ($preset): array => [
                    'id' => $preset->id,
                    'name' => $preset->name,
                    'permissions' => $preset->permissionNames(),
                ])->values(),
            'permission_history' => app(PermissionChangeLogService::class)->historyFor($user),
            // "Rechte wie Person …": Kolleg*innen mit ihren Rechten (nur Name + Rechte, keine Kontaktdaten)
            'colleagues' => User::query()
                ->where('id', '!=', $user->id)
                ->orderBy('last_name')->orderBy('first_name')
                ->with('permissions:id,name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(static fn (User $colleague): array => [
                    'id' => $colleague->id,
                    'name' => trim($colleague->first_name . ' ' . $colleague->last_name),
                    'permissions' => $colleague->permissions->pluck('name')->values()->all(),
                ])->values(),
            'currentTab' => 'permissions',
        ]);
    }

    public function editUserWorkProfile(
        User $user,
        ShiftQualificationRepository $shiftQualificationRepository,
        CraftService $craftService
    ): Response|ResponseFactory {

        $user->load(['assignedCrafts.qualifications', 'shiftQualifications', 'defaultProjectRoles']);

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
                'shiftQualifications' => $shiftQualificationRepository->getAllAvailableOrderedByPosition(),
                'globalQualifications' => $globalQualifications,
                'projectRoles' => ProjectRole::all(),
            ]
        );
    }

    public function updateUserPhoto(User $user, Request $request): void
    {
        if ($user->id !== Auth::user()->id && !Auth::user()->can(PermissionEnum::MA_MANAGER->value)) {
            abort(\Illuminate\Http\Response::HTTP_FORBIDDEN);
        }

        if (isset($request['photo'])) {
            $user->updateProfilePhoto($request['photo']);
        }
    }

    public function deleteUserPhoto(User $user): void
    {
        if ($user->id !== Auth::user()->id && !Auth::user()->can(PermissionEnum::MA_MANAGER->value)) {
            abort(\Illuminate\Http\Response::HTTP_FORBIDDEN);
        }

        // Ersetzt Jetstreams current-user-photo.destroy: die liegt hinter dem
        // Passport-'api'-Guard (Web-Session → Redirect /login → Browser wiederholt
        // DELETE → 405) und würde zudem immer das Foto des EINGELOGGTEN Users löschen.
        $user->deleteProfilePhoto();
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
        if ($user->id !== Auth::user()->id && !Auth::user()->can(PermissionEnum::MA_MANAGER->value)) {
            abort(\Illuminate\Http\Response::HTTP_FORBIDDEN);
        }


        $user->update(
            $request->only(
                'first_name',
                'last_name',
                'phone_number',
                'position',
                'business',
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

        if (Auth::user()->canAny([PermissionEnum::TEAM_UPDATE->value, PermissionEnum::MA_MANAGER->value])) {
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
        if ($user->id !== Auth::user()->id && !Auth::user()->can(PermissionEnum::MA_MANAGER->value)) {
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
        foreach ($request->permissions ?? [] as $permissionToGrant) {
            foreach ($availablePermissions as $availablePermission) {
                if ($availablePermission->value === $permissionToGrant) {
                    $permissionsToGrant[] = $permissionToGrant;
                }
            }
        }
        // Stufenleiter: das stärkste Recht setzt die kleineren Stufen (Backend-Implikation, Konzept Nutzerrechte)
        $permissionsToGrant = app(PermissionImplicationService::class)->expand($permissionsToGrant);

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

        $permissionsBefore = $user->permissions()->pluck('name')->all();
        $rolesBefore = $user->getRoleNames()->all();

        $user->syncPermissions($permissionsToGrant);
        $user->syncRoles($rolesToGrant);
        // Gecachte Inertia-Share-Daten sofort invalidieren statt auf den 5-Min.-TTL zu warten
        $user->forgetCachedShareData();

        // Änderungsverlauf: wer hat wann was vergeben/entzogen
        app(PermissionChangeLogService::class)->log(
            $user,
            Auth::user(),
            $permissionsBefore,
            $permissionsToGrant,
            $rolesBefore,
            $rolesToGrant,
            $request->input('source')
        );

        return Redirect::back();
    }

    public function updateChecklistStatus(Request $request): JsonResponse
    {
        Auth::user()->update([
            'opened_checklists' => $request->opened_checklists
        ]);

        return response()->json(['success' => true]);
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
    public function updateDefaultProjectRoles(User $user, Request $request): RedirectResponse
    {
        $this->authorize('updateWorkProfile', User::class);

        $validated = $request->validate([
            'defaultProjectRoleIds' => 'array',
            'defaultProjectRoleIds.*' => 'integer',
        ]);

        $user->defaultProjectRoles()->sync(
            ProjectRole::whereIn('id', $validated['defaultProjectRoleIds'] ?? [])->pluck('id')
        );

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

        $validated = $request->validate([
            'craftIds' => ['array', 'max:100'],
            'craftIds.*' => ['integer', 'exists:crafts,id'],
        ]);
        $craftIds = $validated['craftIds'] ?? [];

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
            try {
                \Artwork\Modules\Shift\Models\ShiftWorker::withTrashed()
                    ->where('employable_type', \Artwork\Modules\User\Models\User::class)
                    ->where('employable_id', $user->id)
                    ->update(['employable_id' => $reassignUserId, 'deleted_at' => null]);

                ShiftUser::withTrashed()
                    ->where('user_id', $user->id)
                    ->update(['user_id' => $reassignUserId, 'deleted_at' => null]);
            } catch (\Throwable $e) {
                if (function_exists('report')) {
                    report($e);
                }
                // Fallback: ensure no blocking FK remains
                try {
                    \Artwork\Modules\Shift\Models\ShiftWorker::withTrashed()
                        ->where('employable_type', \Artwork\Modules\User\Models\User::class)
                        ->where('employable_id', $user->id)
                        ->forceDelete();
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

            if ($user->projectFilterAndSortSetting) {
                $user->projectFilterAndSortSetting->delete();
            }

            if ($user->userFilterAndSortSetting) {
                $user->userFilterAndSortSetting->delete();
            }

            // Vertragshistorie komplett entfernen (nicht nur den heute gültigen Zeitraum)
            $user->contractAssigns()->get()->each->delete();
            // Reassign all logged activities authored by this user to the
            // replacement user. `changed_by` data that the legacy Antonrom
            // payload embedded in `properties` is rebuilt from `causer` at
            // display time (see RoomCalendarResource and ChangeService),
            // so we only need to fix `causer_id` here.
            Activity::query()
                ->where('causer_id', $user->id)
                ->whereIn('causer_type', [User::class, 'App\\Models\\User'])
                ->update(['causer_id' => $reassignUserId]);
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

        // Block 4: Das Arbeitszeitmuster ist die einzige Quelle für das Soll. Ein mitgesendetes
        // weekly_working_hours wird ignoriert (Spalte bleibt bestehen, wird nicht mehr geschrieben).
        $validated = $request->validate([
            'salary_per_hour' => 'nullable|numeric|min:0',
            'salary_description' => 'nullable|string|max:5000',
        ]);

        $user->update(array_intersect_key($validated, array_flip(['salary_per_hour', 'salary_description'])));
    }


    public function updateCalendarSettings(User $user, Request $request, UserService $userService): void
    {
        $this->authorize('updateOwnPreferences', $user);

        // unsignedSmallInteger-Spalte: ohne Grenzen werfen negative/zu große Werte
        // oder Strings einen SQL-Fehler (500 statt 422)
        $request->validate([
            'calendar_column_width' => 'nullable|integer|between:120,400',
        ]);

        $this->updateShareCalendarDate($user, $request, $userService);

        $settingsFields = $request->only([
            'project_status',
            'project_artists',
            'options',
            'project_management',
            'show_event_creator',
            'show_event_admission',
            'show_event_status',
            'repeating_events',
            'work_shifts',
            'description',
            'event_name',
            'high_contrast',
            'expand_days',
            // Nur vom Kalender-Settings-Modal gesendet (Spalten existieren nur
            // auf user_calendar_settings, nicht auf den Schichtplan-Tabellen)
            'calendar_column_width',
            'show_artist_names_as_title',
            'show_day_remarks',
            'use_event_status_color',
            'use_main_category_color',
            'show_qualifications',
            'shift_notes',
            'hide_unoccupied_rooms',
            'display_project_groups',
            'show_unplanned_events',
            'show_planned_events',
            'hide_unoccupied_days',
            'show_shift_group_tag',
            'show_timeline',
            'show_only_not_fully_staffed_shifts',
            'show_project_assignments',
            // Nur vom Projekt-Schichten-Tab gesendet (Spalten existieren nur
            // auf user_shift_plan_daily_settings)
            'show_unrelated_events',
            'show_unrelated_shifts',
            'show_user_overview'
        ]);

        if ($request->boolean('is_shift_plan')) {
            // updateOrCreate: die Tabellen haben unique(user_id), ein nacktes
            // create() würde im Race mit den parallelen Schichtplan-Requests
            // auf den Unique-Index laufen.
            if ($request->boolean('is_daily_view')) {
                $user->shift_plan_daily_settings()->updateOrCreate([], $settingsFields);
            } else {
                $user->shift_plan_settings()->updateOrCreate([], $settingsFields);
            }
        } elseif ($request->boolean('is_daily_view')) {
            $dailySettings = $user->daily_view_calendar_settings;
            if ($dailySettings === null) {
                $user->daily_view_calendar_settings()->create($settingsFields);
            } else {
                $dailySettings->update($settingsFields);
            }
        } else {
            $user->calendar_settings()->update($settingsFields);
        }
    }

    /**
     * "Zeitraum in allen Ansichten teilen": users-Spalte statt der vier
     * Settings-Tabellen, weil das Setting ansichtsübergreifend wirkt. Beim
     * Einschalten übernehmen alle Ansichten den Zeitraum der Ansicht, aus
     * deren Anzeigeoptionen das Setting aktiviert wurde.
     */
    private function updateShareCalendarDate(User $user, Request $request, UserService $userService): void
    {
        if (!$request->has('share_calendar_date')) {
            return;
        }

        if ($request->boolean('is_shift_plan')) {
            $sourceFilterType = $request->boolean('is_daily_view')
                ? UserFilterTypes::SHIFT_DAILY_FILTER->value
                : UserFilterTypes::SHIFT_FILTER->value;
        } elseif ($request->boolean('is_planning')) {
            $sourceFilterType = $request->boolean('is_daily_view')
                ? UserFilterTypes::PLANNING_DAILY_FILTER->value
                : UserFilterTypes::PLANNING_FILTER->value;
        } else {
            $sourceFilterType = $request->boolean('is_daily_view')
                ? UserFilterTypes::CALENDAR_DAILY_FILTER->value
                : UserFilterTypes::CALENDAR_FILTER->value;
        }

        $userService->updateShareCalendarDateSetting(
            $user,
            $request->boolean('share_calendar_date'),
            $sourceFilterType
        );
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

    public function updateModalBackdrop(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->update([
            'show_modal_backdrop' => $request->boolean('show_modal_backdrop'),
        ]);
    }

    public function updateChecklistStyle(User $user, Request $request): void
    {
        $user->update($request->only([
            'checklist_style'
        ]));
    }

    public function updateZoomFactor(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        // Stufen 0.4–1.4 (Zoom-Dropdown + Legacy-±0.2-Buttons); ohne Validierung
        // landen beliebige Werte in der DB bzw. Strings werfen einen SQL-Fehler
        $request->validate([
            'zoom_factor' => 'required|numeric|between:0.4,1.4',
        ]);

        $user->update($request->only('zoom_factor'));
    }

    public function updateAtAGlance(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->update($request->only('at_a_glance'));
    }

    public function updateShiftPlanZoomFactor(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        // Stufen 0.55/0.75/1 des Schichtplan-Spaltenzooms; ohne Validierung
        // landen beliebige Werte in der DB bzw. Strings werfen einen SQL-Fehler
        $request->validate([
            'zoom_factor' => 'required|numeric|between:0.5,1',
        ]);

        $user->shift_plan_settings()->updateOrCreate([], $request->only('zoom_factor'));
    }

    public function updateBulkSortId(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->update($request->only('bulk_sort_id'));
    }

    public function updateDailyView(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $dailyView = $request->boolean('daily_view');
        // Calendar and shift plan keep their view mode independently. The legacy
        // "daily_view" column is kept in sync as a fallback for un-migrated readers.
        $context = $request->get('context', 'calendar');

        $column = $context === 'shift_plan' ? 'shift_plan_daily_view' : 'calendar_daily_view';

        $user->update([
            $column => $dailyView,
            'daily_view' => $dailyView,
        ]);

        // When switching *into* the day view, seed its date range from the current
        // week-view range so the day view opens where the user currently is
        // ("vom aktuellen Stand übernehmen"). The week filter stays untouched, so
        // switching back returns to exactly where the user left off.
        // Bei geteiltem Zeitraum sind alle Filter ohnehin synchron — der Seed
        // würde den gemeinsamen Zeitraum nur auf 7 Tage kürzen, also überspringen.
        if ($dailyView && !$user->share_calendar_date) {
            $seedMap = $context === 'shift_plan'
                ? [UserFilterTypes::SHIFT_FILTER->value => UserFilterTypes::SHIFT_DAILY_FILTER->value]
                : [
                    UserFilterTypes::CALENDAR_FILTER->value => UserFilterTypes::CALENDAR_DAILY_FILTER->value,
                    UserFilterTypes::PLANNING_FILTER->value => UserFilterTypes::PLANNING_DAILY_FILTER->value,
                ];

            foreach ($seedMap as $weekType => $dailyType) {
                $weekFilter = $user->userFilters()->where('filter_type', $weekType)->first();
                if ($weekFilter?->start_date === null) {
                    continue;
                }

                $start = Carbon::parse($weekFilter->start_date)->startOfDay();

                $user->userFilters()->updateOrCreate(
                    ['filter_type' => $dailyType],
                    [
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $start->copy()->addDays(7)->format('Y-m-d'),
                    ]
                );
            }
        }
    }

    public function updateBulkColumnSize(User $user, Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->update($request->only('bulk_column_size'));

        // Redirect zurückgeben, damit Inertia eine gültige Antwort erhält und die
        // geteilten auth.user-Props (inkl. bulk_column_size) neu lädt – sonst greifen
        // die neuen Spaltenbreiten erst nach einem vollständigen Reload.
        return back();
    }

    public function updateShowDescriptionInBulk(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->update($request->only('show_description_in_bulk'));
    }

    public function updateShiftPeriodOnStartDateChange(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $user->update($request->only('shift_period_on_start_date_change'));
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
        // Eigener Plan nur mit "can view own roster", fremde nur mit
        // Dienstplan-Sichtrechten (UserPolicy::viewOperationPlan).
        $this->authorize('viewOperationPlan', $user);

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

        $pageDto = $userService->getUserShiftPlanPageDto(
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
        );

        // Offene Zeitanpassungs-Anfragen der angezeigten Person: Badge "Zeitanpassung angefragt" auf der Karte
        $pendingWorkTimeChangeRequests = \Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->get(['id', 'shift_id', 'status', 'user_id'])
            ->map(static fn ($requestModel): array => [
                'id' => $requestModel->id,
                'shift_id' => $requestModel->shift_id,
                'status' => $requestModel->status,
                'user_id' => $requestModel->user_id,
            ])
            ->values()
            ->all();

        return Inertia::render(
            'Shifts/UserOperationPlan',
            array_merge($pageDto->toArray(), [
                'pendingWorkTimeChangeRequests' => $pendingWorkTimeChangeRequests,
                // Soll für den angezeigten Zeitraum aus dem Arbeitszeitmuster (statt Wochenstunden/7 im Frontend)
                'workTimeTarget' => $this->operationPlanTargetSummary($user, $pageDto->getDateValue()),
            ])
        );
    }

    private function applyOperationPlanPeriodFromRequest(Request $request, UserService $userService): void
    {
        $rawStart = $request->query('start_date');
        $rawEnd = $request->query('end_date');
        if (!is_string($rawStart) || $rawStart === '' || !is_string($rawEnd) || $rawEnd === '') {
            return;
        }

        try {
            $start = Carbon::parse($rawStart)->startOfDay();
            $end = Carbon::parse($rawEnd)->startOfDay();
        } catch (Throwable) {
            return;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }
        // Gleiche Obergrenze wie der Dienstplan: maximal sechs Monate
        if ($start->diffInDays($end) > 183) {
            $end = $start->copy()->addMonths(6);
        }

        // Nur für diesen Request (Härtung): der gespeicherte Filter der eingeloggten Person
        // bleibt unverändert, getUserShiftPlanPageDto liest den Override aus dem Service.
        $userService->overrideWorkerShiftPlanPeriod($start, $end);
    }

    public function compactMode(User $user, Request $request): void
    {
        $user->update($request->only('compact_mode'));
    }

    public function toggleShowProjectTeamNames(User $user, Request $request): void
    {
        $user->update($request->only('show_project_team_names'));
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

        // Farben kommen aus der Query — nur echte Hex-Werte durchlassen,
        // sonst landet beliebiger Text in den SVG-Attributen.
        // mixed, weil ?bg[]=... als Array ankommen kann
        $hex = static fn (mixed $value, string $fallback): string =>
            is_string($value) && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) ? $value : $fallback;

        $bgColor = $hex(request()?->query('bg'), '#00a3ff'); // Standard: Blau
        $textColor = $hex(request()?->query('color'), '#ffffff'); // Standard: Weiß

        // SVG in Blade rendern. Fuer ein Initialen-Paar ist das Bild konstant,
        // deshalb aggressiv cachen — sonst holt der Browser es pro Termin neu.
        return response()->view('avatar', compact('letters', 'bgColor', 'textColor'))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
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

    public function updateOpenedCrafts(User $user, Request $request): void
    {
        $user->update($request->only('opened_crafts'));
    }

    public function updateSortWorkersByQualification(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $request->validate(['sort_workers_by_qualification' => ['required', 'boolean']]);

        $user->update($request->only('sort_workers_by_qualification'));
    }

    public function updateClosedQualificationGroups(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $request->validate([
            'closed_qualification_groups' => ['nullable', 'array'],
            'closed_qualification_groups.*' => ['string'],
        ]);

        $user->update($request->only('closed_qualification_groups'));
    }

    public function updateShowQualificationDuplicates(User $user, Request $request): void
    {
        $this->authorize('updateOwnPreferences', $user);

        $request->validate(['show_qualification_duplicates' => ['required', 'boolean']]);

        $user->update($request->only('show_qualification_duplicates'));
    }
}

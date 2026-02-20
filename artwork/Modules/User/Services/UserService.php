<?php

namespace Artwork\Modules\User\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Inventory\Services\ProductBasketService;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\User\DTOs\UserShiftPlanPageDto;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Events\UserUpdated;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Repositories\UserRepository;
use Artwork\Modules\User\Models\UserCalendarFilter;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Services\UserProjectManagementSettingService;
use Artwork\Modules\User\Models\UserShiftCalendarFilter;
use Artwork\Modules\User\Services\UserUserManagementSettingService;
use Artwork\Modules\User\Models\UserWorkerShiftPlanFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\AuthManager;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationSettingService $notificationSettingService,
        private readonly StatefulGuard $statefulGuard,
        private readonly BroadcastManager $broadcastManager,
        private readonly UserUserManagementSettingService $userUserManagementSettingService,
        private readonly UserProjectManagementSettingService $userProjectManagementSettingService,
        private readonly CarbonService $carbonService,
        private readonly ProjectTabService $projectTabService,
        protected ProductBasketService $productBasketService,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(
        array $attributes,
        array $roles,
        array $permissions,
        array $departmentIds
    ): User {
        /** @var User $user */
        $this->userRepository->saveOrFail(
            ($user = $this->userRepository->getNewModelInstance())->fill($attributes)
        );

        foreach ($this->notificationSettingService->getNotificationEnumCases() as $notificationType) {
            $this->notificationSettingService->create(
                [
                    'user_id' => $user->getAttribute('id'),
                    'group_type' => $notificationType->groupType(),
                    'type' => $notificationType->value,
                    'title' => $notificationType->title(),
                    'description' => $notificationType->description()
                ]
            );
        }

        $this->statefulGuard->login($user);

        $this->broadcastManager->event(new UserUpdated())->toOthers();

        $this->userRepository->syncDepartments($user, $departmentIds);

        $user->assignRole(...$roles);
        $user->givePermissionTo(...$permissions);
        $user->calendar_settings()->create();
        $user->userFilters()->create([
            'filter_type' => UserFilterTypes::CALENDAR_FILTER->value,
            'start_date' => Carbon::now()->startOfDay(),
            'end_date' => Carbon::now()->addWeeks(2)->endOfDay()
        ]);

        $user->userFilters()->create([
            'filter_type' => UserFilterTypes::PLANNING_FILTER->value,
            'start_date' => Carbon::now()->startOfDay(),
            'end_date' => Carbon::now()->addWeeks(2)->endOfDay()
        ]);

        $user->userFilters()->create([
            'filter_type' => UserFilterTypes::SHIFT_FILTER->value,
            'start_date' => Carbon::now()->startOfDay(),
            'end_date' => Carbon::now()->addWeeks(2)->endOfDay()
        ]);

        $this->userUserManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userUserManagementSettingService->getDefaults()
        );
        $this->userProjectManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userProjectManagementSettingService->getDefaults()
        );

        $this->productBasketService->createBasisBasket($user);

        return $user;
    }

    public function searchUsers(string $search): \Illuminate\Support\Collection
    {
        return $this->userRepository->searchUsers($search);
    }

    public function getAuthUser(bool $needCalendarAbo = false): ?User
    {
        /** @var User $user */
        $user = Auth::user();
        if ($needCalendarAbo && !$user->calendarAbo) {
            $user->load(['calendarAbo']);
        }

        return $user;
    }

    /**
     * @return Collection
     */
    public function getAllUsers(): Collection
    {
        return $this->userRepository->getAllUsers();
    }

    /**
     * @param int $id
     * @return User
     */
    public function findUser(int $id): User
    {
        return $this->userRepository->findUser($id);
    }

    public function getAuthUserCrafts(): Collection
    {
        return $this->getAuthUser()->assignedCrafts;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getUserShiftPlanPageDto(
        User $user,
        CalendarService $calendarService,
        EventService $eventService,
        RoomService $roomService,
        EventTypeService $eventTypeService,
        ProjectService $projectService,
        ShiftQualificationService $shiftQualificationService,
        Carbon $selectedPeriodDate,
        Carbon $selectedDate,
        ?string $month,
        ?string $vacationMonth
    ): UserShiftPlanPageDto {
        [$requestedStartDate, $requestedEndDate] = $this->getUserWorkerShiftPlanFilterStartAndEndDatesOrDefault(
            $this->getAuthUser()
        );

        // Synchronize month with workerShiftPlanFilter dates for bidirectional sync
        // Always derive month from workerShiftPlanFilter to keep both components in sync
        $month = $requestedStartDate->format('Y-m-d');

        // Derive the displayed calendar month from $month (which is always in sync with the calendar)
        $calendarMonth = Carbon::parse($month)->startOfMonth();

        $requestedPeriod = iterator_to_array(
            CarbonPeriod::create($requestedStartDate, $requestedEndDate)->map(
                function (Carbon $date) {
                    return $date->format('d.m.Y');
                }
            )
        );

        $startOfWeek = $requestedStartDate->copy()->startOfWeek();
        $endOfWeek = $requestedEndDate->copy()->endOfWeek();

        $daysWithData = $eventService->getDaysWithEventsAndTotalPlannedWorkingHours(
            $user->id,
            'user',
            $startOfWeek,
            $endOfWeek
        );

        [
            $calendarData,
            $dateToShow
        ] = $calendarService->getAvailabilityData(
            $user,
            $month
        );

        return UserShiftPlanPageDto::newInstance()
            ->setUserToEdit(UserShowResource::make($user))
            ->setUserToEditWholeWeekDatePeriodVacations(
                $user->getAttribute('vacations')
                    ->whereBetween(
                        'date',
                        [
                            $startOfWeek->format('Y-m-d'),
                            $endOfWeek->format('Y-m-d')
                        ]
                    )
            )
            ->setCrafts(Craft::all())
            ->setCurrentTab('shiftplan')
            ->setCalendarData($calendarData)
            ->setDateToShow($dateToShow)
            ->setCreateShowDate(
                [
                    $calendarMonth->copy()->locale($selectedPeriodDate->locale)->isoFormat('MMMM YYYY'),
                    $calendarMonth->copy()->startOfMonth()->toDate()
                ]
            )
            ->setShowVacationsAndAvailabilitiesDate($selectedDate->format('Y-m-d'))
            ->setDateValue([$requestedStartDate->format('Y-m-d'), $requestedEndDate->format('Y-m-d')])
            ->setWholeWeekDatePeriod(
                iterator_to_array(
                    CarbonPeriod::create($startOfWeek, $endOfWeek)
                        ->map(
                            function (Carbon $date) use ($requestedPeriod) {
                                return [
                                    'inRequestedTimeSpan' => in_array(
                                        $date->format('d.m.Y'),
                                        $requestedPeriod,
                                        true
                                    ),
                                    'full_day' => $date->format('d.m.Y'),
                                    'day' => $date->format('d.m.'),
                                    'day_string' => $date->shortDayName,
                                    'week_number' => $date->weekOfYear,
                                    'month_number' => $date->month,
                                    'is_monday' => $date->isMonday(),
                                    'is_weekend' => $date->isWeekend(),
                                    'day_without_format' => $date->format('Y-m-d'),
                                ];
                            }
                        )
                )
            )
            //->setEventsWithTotalPlannedWorkingHours($eventsWithTotalPlannedWorkingHours)
            //->setTotalPlannedWorkingHours((float)$totalPlannedWorkingHours)
            ->setVacationSelectCalendar($calendarService->createVacationAndAvailabilityPeriodCalendar($vacationMonth))
            ->setRooms($roomService->getAllWithoutTrashed())
            ->setProjects($projectService->getAll())
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setShifts($this->getUserShiftsOrderedByStartAscending($user))
            ->setVacations($this->getUserVacationsByMonthOrderedByDateAsc($user, $calendarMonth))
            ->setAvailabilities($this->getUserAvailabilitiesByMonthOrderedByDateAsc($user, $calendarMonth))
            ->setFirstProjectShiftTabId(
                $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            );
    }

    public function getUserVacationsByDateOrderedByDateAsc(int|User $user, Carbon $selectedDate): Collection
    {
        return $this->userRepository->getUserVacationsByDateOrderedByDateAsc($user, $selectedDate);
    }

    public function getUserVacationsByMonthOrderedByDateAsc(int|User $user, Carbon $monthDate): Collection
    {
        return $this->userRepository->getUserVacationsByMonthOrderedByDateAsc($user, $monthDate);
    }

    public function getUserAvailabilitiesByDateOrderedByDateAsc(int|User $user, Carbon $selectedDate): Collection
    {
        return $this->userRepository->getUserAvailabilitiesByDateOrderedByDateAsc(
            $user,
            $selectedDate
        );
    }

    public function getUserAvailabilitiesByMonthOrderedByDateAsc(int|User $user, Carbon $monthDate): Collection
    {
        return $this->userRepository->getUserAvailabilitiesByMonthOrderedByDateAsc($user, $monthDate);
    }

    public function getUserShiftsOrderedByStartAscending(int|User $user): Collection
    {
        return $this->userRepository->getShiftsOrderedByStartAscending($user);
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserCalendarFilterDatesOrDefault(UserFilter $userCalendarFilter): array
    {
        $startDate = $userCalendarFilter->start_date
            ? Carbon::parse($userCalendarFilter->start_date)->startOfDay()
            : now()->startOfDay();

        $endDate = $userCalendarFilter->end_date
            ? Carbon::parse($userCalendarFilter->end_date)->endOfDay()
            : now()->addWeeks()->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserShiftCalendarFilterDatesOrDefault(User $user): array
    {
        $userShiftCalendarFilter = $user->userFilters()->shiftFilter()->first();

        $hasUserShiftCalendarFilterDates = !is_null($userShiftCalendarFilter?->start_date) &&
            !is_null($userShiftCalendarFilter?->end_date);
        $startDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($userShiftCalendarFilter->start_date)->startOfDay() :
            Carbon::now()->startOfDay();
        $endDate = $hasUserShiftCalendarFilterDates ?
            Carbon::create($userShiftCalendarFilter->end_date)->endOfDay() :
            Carbon::now()->addWeeks()->endOfDay();

        return [$startDate, $endDate];
    }

    public function getUserWorkerShiftPlanFilter(User $user, array $attributes = []): UserWorkerShiftPlanFilter
    {
        /** @var UserWorkerShiftPlanFilter $userWorkerShiftPlanFilter */
        $userWorkerShiftPlanFilter = $user->workerShiftPlanFilter()->firstOrCreate(
            [
                'user_id' => $user->getAttribute('id')
            ],
            $attributes
        );

        return $userWorkerShiftPlanFilter;
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserWorkerShiftPlanFilterStartAndEndDatesOrDefault(User $user): array
    {
        $userWorkerShiftPlanFilter = $this->getUserWorkerShiftPlanFilter(
            $user,
            [
                'start_date' => ($now = $this->carbonService->getNow()),
                'end_date' => $this->carbonService->cloneAndAddWeek($now)
            ]
        );

        $startDate = $userWorkerShiftPlanFilter->getAttribute('start_date');
        $endDate = $userWorkerShiftPlanFilter->getAttribute('end_date');

        // Ensure dates are valid Carbon instances
        if (!$startDate instanceof Carbon) {
            $startDate = $now ?? $this->carbonService->getNow();
        }
        if (!$endDate instanceof Carbon) {
            $endDate = $this->carbonService->cloneAndAddWeek($startDate);
        }

        // Ensure start date is not after end date
        if ($startDate->isAfter($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        // Ensure dates are not equal (CarbonPeriod requires start < end)
        if ($startDate->equalTo($endDate)) {
            $endDate = $startDate->copy()->addDay();
        }

        return [$startDate, $endDate];
    }

    public function getAdminUser(): User
    {
        return $this->userRepository->getAdminUser();
    }

    public function atAGlanceEnabled(User|int|null $user = null): bool
    {
        return $this->userRepository->atAGlanceEnabled(
            is_int($user) ?
                $this->findUser($user) :
                ($user instanceof User ?
                    $user :
                        $this->getAuthUser())
        );
    }

    public function update(int|User $user, array $attributes): User
    {
        $this->userRepository->update(
            !$user instanceof User ? ($user = $this->userRepository->findUserOrFail($user)) : $user,
            $attributes
        );

        return $user;
    }

    public function getNotReadOfNotificationTypeNotSentInSummaryForUser(
        User $user,
        string $notificationConstValue
    ): Collection {
        return $this->userRepository->getNotReadOfNotificationTypeNotSentInSummaryForUser(
            $user,
            $notificationConstValue
        );
    }

    public function getAuthUserId(): int
    {
        return $this->getAuthUser()->getAttribute('id');
    }

    public function updateCurrentUserShowNotificationIndicator(User $user, bool $shown): User
    {
        $this->update(
            $user,
            [
                'show_notification_indicator' => $shown
            ]
        );

        return $user;
    }

    public function shareCalendarAbo(string $type = 'calendar'): void
    {
        /** @var User $user */
        $user = $this->getAuthUser();

        $calendarAbo = null;
        $shiftCalendarAbo = null;

        if ($type === 'calendar') {
            $calendarAbo = $user->relationLoaded('calendarAbo')
                ? $user->calendarAbo
                : $user->load('calendarAbo')->calendarAbo;
        }

        if ($type === 'shiftCalendar') {
            $shiftCalendarAbo = $user->relationLoaded('shiftCalendarAbo')
                ? $user->shiftCalendarAbo
                : $user->load('shiftCalendarAbo')->shiftCalendarAbo;
        }

        Inertia::share([
            'calendarAbo' => $calendarAbo,
            'calendarAboShift' => $shiftCalendarAbo,
        ]);
    }

}

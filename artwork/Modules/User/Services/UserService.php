<?php

namespace Artwork\Modules\User\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Services\ProjectTabService;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\DTOs\UserShiftPlanPageDto;
use Artwork\Modules\User\Events\UserUpdated;
use Artwork\Modules\User\Http\Resources\UserShiftPlanResource;
use Artwork\Modules\User\Http\Resources\UserShowResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Artwork\Modules\UserProjectManagementSetting\Services\UserProjectManagementSettingService;
use Artwork\Modules\UserUserManagementSetting\Services\UserUserManagementSettingService;
use Artwork\Modules\UserWorkerShiftPlanFilter\Models\UserWorkerShiftPlanFilter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Auth\AuthManager;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
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
        private readonly WorkingHourService $workingHourService,
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
        $user->calendar_filter()->create();
        $user->shift_calendar_filter()->create();

        $this->userUserManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userUserManagementSettingService->getDefaults()
        );
        $this->userProjectManagementSettingService->updateOrCreateIfNecessary(
            $user,
            $this->userProjectManagementSettingService->getDefaults()
        );
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

        $requestedPeriod = iterator_to_array(
            CarbonPeriod::create($requestedStartDate, $requestedEndDate)->map(
                function (Carbon $date) {
                    return $date->format('d.m.Y');
                }
            )
        );

        //dd($requestedPeriod);

        $startOfWeek = $requestedStartDate->copy()->startOfWeek();
        $endOfWeek = $requestedEndDate->copy()->endOfWeek();

        $daysWithData = $eventService->getDaysWithEventsAndTotalPlannedWorkingHours(
            $user->id,
            'users',
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
            ->setEventTypes(EventTypeResource::collection($eventTypeService->getAll())->resolve())
            ->setCurrentTab('shiftplan')
            ->setCalendarData($calendarData)
            ->setDateToShow($dateToShow)
            ->setCreateShowDate(
                [
                    $selectedPeriodDate->isoFormat('MMMM YYYY'),
                    $selectedPeriodDate->copy()->startOfMonth()->toDate()
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
            ->setVacations($this->getUserVacationsByDateOrderedByDateAsc($user, $selectedDate))
            ->setAvailabilities($this->getUserAvailabilitiesByDateOrderedByDateAsc($user, $selectedDate))
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

    public function getUserAvailabilitiesByDateOrderedByDateAsc(int|User $user, Carbon $selectedDate): Collection
    {
        return $this->userRepository->getUserAvailabilitiesByDateOrderedByDateAsc(
            $user,
            $selectedDate
        );
    }

    public function getUserShiftsOrderedByStartAscending(int|User $user): Collection
    {
        return $this->userRepository->getShiftsOrderedByStartAscending($user);
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserCalendarFilterDatesOrDefault(?User $user = null): array
    {
        if (!$user instanceof User) {
            $user = $this->getAuthUser();
        }

        $userCalendarFilter = $user->getAttribute('calendar_filter');
        $hasUserCalendarFilterDates = !is_null($userCalendarFilter?->getAttribute('start_date')) &&
            !is_null($userCalendarFilter?->getAttribute('end_date'));

        $startDate = $hasUserCalendarFilterDates ?
            Carbon::create($userCalendarFilter->getAttribute('start_date'))->startOfDay() :
            Carbon::now()->startOfDay();
        $endDate = $hasUserCalendarFilterDates ?
            Carbon::create($userCalendarFilter->getAttribute('end_date'))->endOfDay() :
            Carbon::now()->addWeeks()->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * @return array<int, Carbon>
     */
    public function getUserShiftCalendarFilterDatesOrDefault(User $user): array
    {
        $userShiftCalendarFilter = $user->shift_calendar_filter;

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
        return [
            (
                $userWorkerShiftPlanFilter = $this->getUserWorkerShiftPlanFilter(
                    $user,
                    [
                        'start_date' => ($now = $this->carbonService->getNow()),
                        'end_date' => $this->carbonService->cloneAndAddWeek($now)
                    ]
                )
            )->getAttribute('start_date'),
            $userWorkerShiftPlanFilter->getAttribute('end_date')
        ];
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
}

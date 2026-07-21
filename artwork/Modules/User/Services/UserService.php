<?php

namespace Artwork\Modules\User\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Vacation\Models\Vacation;
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
        $user->forgetCachedShareData();
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

        // Verfügbarkeitskalender ist vom Einsatzplan-Filter entkoppelt: expliziter month-Parameter
        // gewinnt, sonst startet der Kalender im Monat des Einsatzplan-Filters.
        $month = $month ?: $requestedStartDate->format('Y-m-d');

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
            ->setCrafts(static fn() => Craft::all())
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
            ->setDaysWithData(
                fn() => $eventService->getDaysWithEventsAndTotalPlannedWorkingHours(
                    $user->id,
                    'user',
                    $startOfWeek,
                    $endOfWeek
                )
            )
            //->setEventsWithTotalPlannedWorkingHours($eventsWithTotalPlannedWorkingHours)
            //->setTotalPlannedWorkingHours((float)$totalPlannedWorkingHours)
            // Schwere Props als Closures: Inertia wertet sie bei partiellen Reloads
            // (z.B. nach Speichern/Löschen von Verfügbarkeiten) gar nicht erst aus
            ->setVacationSelectCalendar(
                static fn() => $calendarService->createVacationAndAvailabilityPeriodCalendar($vacationMonth)
            )
            ->setRooms(static fn() => $roomService->getAllWithoutTrashed())
            ->setProjects(static fn() => $projectService->getAll())
            ->setShiftQualifications(static fn() => $shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setShifts(fn() => $this->getUserShiftsOrderedByStartAscending($user))
            ->setVacations(
                tap(
                    $this->getUserVacationsByMonthOrderedByDateAsc($user, $calendarMonth),
                    static fn($vacations) => Vacation::attachSeriesDateBounds($vacations)
                )
            )
            ->setAvailabilities(
                tap(
                    $this->getUserAvailabilitiesByMonthOrderedByDateAsc($user, $calendarMonth),
                    static fn($availabilities) => Availability::attachSeriesDateBounds($availabilities)
                )
            )
            ->setFirstProjectShiftTabId(
                fn() => $this->projectTabService->getFirstProjectTabWithTypeIdOrFirstProjectTabId(
                    ProjectTabComponentEnum::SHIFT_TAB
                )
            )
            // Projektzuordnungen/-wünsche je Tag (Map Y-m-d => Einträge) für die
            // Anzeige in den Einsatzplan-Tageszellen
            ->setProjectAssignments(
                static fn() => app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class)
                    ->getAssignmentsGroupedByDate(User::class, [$user->id], $startOfWeek, $endOfWeek)
                    ->get($user->id) ?? collect()
            )
            // Projektwünsche im angezeigten Kalendermonat (Verfügbarkeitskalender,
            // dessen Monat vom Einsatzplan-Filter entkoppelt ist)
            ->setProjectWishes(
                static fn() => app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class)
                    ->getSerializedAssignmentsForEmployable(
                        User::class,
                        $user->id,
                        $calendarMonth->copy()->startOfMonth(),
                        $calendarMonth->copy()->endOfMonth(),
                        \Artwork\Modules\Project\Enum\ProjectDayAssignmentType::WISH
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
     * Quelle für das Einschalt-Seeding, wenn das Setting aus den
     * Anzeigeeinstellungen der Inventar-Artikelplanung aktiviert wird
     * (die Artikelplanung hat keinen user_filters-Eintrag).
     */
    public const SHARE_DATE_SOURCE_INVENTORY_ARTICLE_PLAN = 'inventory_article_plan';

    /**
     * Schreibt denselben Zeitraum in alle Ansichts-Filter (Kalender, Planung,
     * Schichtplan, deren Tagesansichten, die Listenansicht und die
     * Inventar-Artikelplanung). Wird genutzt, wenn der User "Zeitraum in
     * allen Ansichten teilen" aktiviert hat.
     * PROJECT_SHIFT_FILTER bleibt außen vor — der Projekt-Schichten-Tab folgt
     * dem Projektzeitraum und nicht der globalen Navigation.
     */
    public function syncSharedCalendarFilterDates(User $user, string $startDate, string $endDate): void
    {
        foreach (UserFilterTypes::cases() as $filterType) {
            if ($filterType === UserFilterTypes::PROJECT_SHIFT_FILTER) {
                continue;
            }

            $user->userFilters()->updateOrCreate(
                ['filter_type' => $filterType->value],
                [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            );
        }

        $user->inventoryArticlePlanFilter()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );
    }

    /**
     * Speichert das "Zeitraum in allen Ansichten teilen"-Setting. Beim
     * Einschalten werden alle Ansichten sofort auf den Zeitraum der Ansicht
     * gesetzt, aus der das Setting geändert wurde ($sourceFilterType).
     */
    public function updateShareCalendarDateSetting(User $user, bool $enabled, string $sourceFilterType): void
    {
        $wasEnabled = (bool) $user->getAttribute('share_calendar_date');
        $user->update(['share_calendar_date' => $enabled]);

        if (!$enabled || $wasEnabled) {
            return;
        }

        $sourceFilter = $sourceFilterType === self::SHARE_DATE_SOURCE_INVENTORY_ARTICLE_PLAN
            ? $user->inventoryArticlePlanFilter()->first()
            : $user->userFilters()->where('filter_type', $sourceFilterType)->first();
        if ($sourceFilter?->start_date === null || $sourceFilter?->end_date === null) {
            return;
        }

        $this->syncSharedCalendarFilterDates(
            $user,
            Carbon::parse($sourceFilter->start_date)->format('Y-m-d'),
            Carbon::parse($sourceFilter->end_date)->format('Y-m-d')
        );
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

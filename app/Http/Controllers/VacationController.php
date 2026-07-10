<?php

namespace App\Http\Controllers;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Events\UpdateShiftInShiftPlan;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Artwork\Modules\Vacation\Https\Requests\CreateVacationRequest;
use Artwork\Modules\Vacation\Https\Requests\UpdateVacationRequest;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\VacationSeries;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Vacation\Services\VacationSeriesService;
use Artwork\Modules\Vacation\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Artwork\Modules\Vacation\Enums\Vacation as VacationEnum;

class VacationController extends Controller
{
    public function __construct(
        private readonly VacationService $vacationService,
        private readonly AvailabilityService $availabilityService,
        private readonly VacationSeriesService $vacationSeriesService,
        private readonly AvailabilityConflictService $availabilityConflictService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly ChangeService $changeService,
        private readonly SchedulingService $schedulingService,
        private readonly NotificationService $notificationService,
        private readonly WorkingHourCacheService $workingHourCacheService,
    ) {
    }

    public function store(
        CreateVacationRequest $createVacationRequest,
        User $user,
        AvailabilityConflictService $availabilityConflictService,
        AvailabilitySeriesService $availabilitySeriesService
    ): void {
        // Nur für sich selbst anlegen – fremde Urlaube/Verfügbarkeiten brauchen "can manage availability".
        abort_unless(
            (int) $user->id === (int) auth()->id()
                || (bool) auth()->user()?->can(
                    \Artwork\Modules\Permission\Enums\PermissionEnum::AVAILABILITY_MANAGEMENT->value
                ),
            403
        );

        if ($createVacationRequest->type === 'vacation') {
            $this->vacationService->create(
                $user,
                $createVacationRequest,
                $this->vacationConflictService,
                $this->vacationSeriesService,
                $this->changeService,
                $this->schedulingService,
                $this->notificationService
            );
        } else {
            $this->availabilityService->create(
                $user,
                $createVacationRequest,
                $this->notificationService,
                $availabilityConflictService,
                $availabilitySeriesService,
                $this->changeService,
                $this->schedulingService
            );
        }
    }

    public function storeFreelancerVacation(
        CreateVacationRequest $createVacationRequest,
        Freelancer $freelancer,
        AvailabilitySeriesService $availabilitySeriesService
    ): void {
        // Freelancer sind "Worker": Worker-Manager dürfen deren Urlaube/Verfügbarkeiten anlegen
        // (Frontend gated auf "can manage workers"); Verfügbarkeits-Manager ebenfalls.
        abort_unless(
            (bool) auth()->user()?->can(\Artwork\Modules\Permission\Enums\PermissionEnum::MA_MANAGER->value)
                || (bool) auth()->user()?->can(
                    \Artwork\Modules\Permission\Enums\PermissionEnum::AVAILABILITY_MANAGEMENT->value
                ),
            403
        );

        if ($createVacationRequest->type === 'vacation') {
            $this->vacationService->create(
                $freelancer,
                $createVacationRequest,
                $this->vacationConflictService,
                $this->vacationSeriesService,
                $this->changeService,
                $this->schedulingService,
                $this->notificationService
            );
        } else {
            $this->availabilityService->create(
                $freelancer,
                $createVacationRequest,
                $this->notificationService,
                $this->availabilityConflictService,
                $availabilitySeriesService,
                $this->changeService,
                $this->schedulingService
            );
        }
    }

    public function checkVacation(
        Request $request,
        User $user
    ): void {
        $day = Carbon::parse($request->day)->format('Y-m-d');
        $checked = $request->get('checked');
        $vacationTypeBeforeUpdate = $request->get('vacationTypeBeforeUpdate');
        // DP-18: "Frei" (FREE_WORK) kann als Ganzer freier Tag (full) oder Halber freier Tag
        // (morning|afternoon) gesetzt werden.
        $dayPart = $request->get('dayPart');
        $isFree = $checked['type'] === 'FREE_WORK';
        $isHalf = $isFree && in_array($dayPart, ['morning', 'afternoon'], true);
        $resolvedDayPart = $isFree ? ($isHalf ? $dayPart : 'full') : null;
        $vacations = $this->vacationService->findVacationWithinInterval($user, $day);
        if ($checked['type'] === VacationEnum::AVAILABLE->value) {
            if ($vacations->count() > 0) {
                $this->vacationService->deleteVacationInterval($user, $day);
                $this->broadcastShiftPlanUpdates($this->shiftsOnDay($user, $day));
            }
            return;
        }

        if ($vacations->count() === 0) {
            $createVacationRequest = new CreateVacationRequest([
                'date' => $day,
                'type' => 'vacation',
                'full_day' => !$isHalf,
                'day_part' => $resolvedDayPart,
                'is_series' => false,
                'comment' => $checked['type'],
            ]);
            $this->vacationService->create(
                $user,
                $createVacationRequest,
                $this->vacationConflictService,
                $this->vacationSeriesService,
                $this->changeService,
                $this->schedulingService,
                $this->notificationService,
                $checked['type']
            );
        } else {
            foreach ($vacations as $vacation) {
                if ((string)$vacation->comment === (string)$vacationTypeBeforeUpdate['type']) {
                    $vacation->update([
                        'type' => $checked['type'],
                        'comment' => $checked['type'],
                        'full_day' => !$isHalf,
                        'day_part' => $resolvedDayPart,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
            $this->workingHourCacheService->forgetForEntity('user', $user->id);
        }

        // Schichten VOR einem evtl. Detach einsammeln, damit auch entfernte
        // Zuweisungen anschließend frisch in den Schichtplan gebroadcastet werden.
        $shiftsOnDay = $this->shiftsOnDay($user, $day);

        // Shift assignments are detached automatically unless the caller handles removal itself
        // (e.g. the shift-plan modal, which asks the planner first and may keep the assignments).
        // Festgeschriebene Schichten bleiben immer bestehen (Stundenabrechnung).
        if ($request->boolean('remove_from_shifts', true)) {
            $shifts = $user->shifts()
                ->where('event_start_day', $day)
                ->where('shifts.is_committed', false)
                ->get();
            foreach ($shifts as $shift) {
                $shift->users()->detach($user->id);
            }
        }

        $this->broadcastShiftPlanUpdates($shiftsOnDay);
    }

    public function checkVacationFreelancer(
        Request $request,
        Freelancer $freelancer
    ): void {
        $day = Carbon::parse($request->day)->format('Y-m-d');
        $checked = $request->get('checked');
        $vacationTypeBeforeUpdate = $request->get('vacationTypeBeforeUpdate');
        $vacations = $this->vacationService->findVacationWithinInterval($freelancer, $day);
        if ($checked['type'] === VacationEnum::AVAILABLE->value) {
            if ($vacations->count() > 0) {
                $this->vacationService->deleteVacationInterval($freelancer, $day);
                $this->broadcastShiftPlanUpdates($this->shiftsOnDay($freelancer, $day));
            }
            return;
        }

        if ($vacations->count() === 0) {
            $createVacationRequest = new CreateVacationRequest([
                'date' => $day,
                'type' => 'vacation',
                'full_day' => true,
                'is_series' => false,
                'comment' => $checked['type'],
            ]);
            $this->vacationService->create(
                $freelancer,
                $createVacationRequest,
                $this->vacationConflictService,
                $this->vacationSeriesService,
                $this->changeService,
                $this->schedulingService,
                $this->notificationService,
                $checked['type']
            );
        } else {
            foreach ($vacations as $vacation) {
                if ((string)$vacation->comment === (string)$vacationTypeBeforeUpdate['type']) {
                    $vacation->update([
                        'type' => $checked['type'],
                        'comment' => $checked['type'],
                        'created_by' => auth()->id(),
                    ]);
                }
            }
            $this->workingHourCacheService->forgetForEntity('freelancer', $freelancer->id);
        }

        $shiftsOnDay = $this->shiftsOnDay($freelancer, $day);

        // Festgeschriebene Schichten bleiben immer bestehen (Stundenabrechnung).
        if ($request->boolean('remove_from_shifts', true)) {
            $shifts = $freelancer->shifts()
                ->where('event_start_day', $day)
                ->where('shifts.is_committed', false)
                ->get();
            foreach ($shifts as $shift) {
                $shift->freelancer()->detach($freelancer->id);
            }
        }

        $this->broadcastShiftPlanUpdates($shiftsOnDay);
    }

    public function checkVacationServiceProvider(
        Request $request,
        ServiceProvider $serviceProvider
    ): void {
        $day = Carbon::parse($request->day)->format('Y-m-d');
        $checked = $request->get('checked');
        $vacationTypeBeforeUpdate = $request->get('vacationTypeBeforeUpdate');
        $vacations = $this->vacationService->findVacationWithinInterval($serviceProvider, $day);
        if ($checked['type'] === VacationEnum::AVAILABLE->value) {
            if ($vacations->count() > 0) {
                $this->vacationService->deleteVacationInterval($serviceProvider, $day);
                $this->broadcastShiftPlanUpdates($this->shiftsOnDay($serviceProvider, $day));
            }
            return;
        }

        if ($vacations->count() === 0) {
            $createVacationRequest = new CreateVacationRequest([
                'date' => $day,
                'type' => 'vacation',
                'full_day' => true,
                'is_series' => false,
                'comment' => $checked['type'],
            ]);
            $this->vacationService->create(
                $serviceProvider,
                $createVacationRequest,
                $this->vacationConflictService,
                $this->vacationSeriesService,
                $this->changeService,
                $this->schedulingService,
                $this->notificationService,
                $checked['type']
            );
        } else {
            foreach ($vacations as $vacation) {
                if ((string)$vacation->comment === (string)$vacationTypeBeforeUpdate['type']) {
                    $vacation->update([
                        'type' => $checked['type'],
                        'comment' => $checked['type'],
                        'created_by' => auth()->id(),
                    ]);
                }
            }
            $this->workingHourCacheService->forgetForEntity('service_provider', $serviceProvider->id);
        }

        $shiftsOnDay = $this->shiftsOnDay($serviceProvider, $day);

        // Festgeschriebene Schichten bleiben immer bestehen (Stundenabrechnung).
        if ($request->boolean('remove_from_shifts', true)) {
            $shifts = $serviceProvider->shifts()
                ->where('event_start_day', $day)
                ->where('shifts.is_committed', false)
                ->get();
            foreach ($shifts as $shift) {
                $shift->serviceProvider()->detach($serviceProvider->id);
            }
        }

        $this->broadcastShiftPlanUpdates($shiftsOnDay);
    }

    /**
     * Alle Schichten der Person, die den Tag berühren (inkl. mehrtägiger Schichten).
     */
    private function shiftsOnDay(User|Freelancer|ServiceProvider $vacationer, string $day): Collection
    {
        return $vacationer->shifts()
            ->whereDate('shifts.start_date', '<=', $day)
            ->whereDate('shifts.end_date', '>=', $day)
            ->get()
            ->values();
    }

    /**
     * Betroffene Schichten frisch in den Schichtplan broadcasten, damit die
     * Verfügbarkeits-Warnung (workers[].is_unavailable) ohne Reload aktuell ist.
     */
    private function broadcastShiftPlanUpdates(Collection $shifts): void
    {
        foreach ($shifts as $shift) {
            $roomId = $shift->room_id ?? $shift->event?->room_id;
            if ($roomId === null) {
                continue;
            }
            // Relations verwerfen, damit broadcastWith den Stand NACH der Änderung lädt
            $shift->unsetRelations();
            broadcast(new UpdateShiftInShiftPlan($shift, (int) $roomId));
        }
    }

    public function update(
        UpdateVacationRequest $updateVacationRequest,
        Vacation $vacation,
        AvailabilitySeriesService $availabilitySeriesService
    ): RedirectResponse {
        $this->authorize('update', $vacation);
        if ($updateVacationRequest->validated()) {
            if ($updateVacationRequest->type_before_update !== $updateVacationRequest->type) {
                if ($updateVacationRequest->type === 'available') {
                    if ($vacation->vacationer_type === User::class) {
                        $this->availabilityService->create(
                            User::find($vacation->vacationer_id),
                            $updateVacationRequest,
                            $this->notificationService,
                            $this->availabilityConflictService,
                            $availabilitySeriesService,
                            $this->changeService,
                            $this->schedulingService
                        );
                    } elseif ($vacation->vacationer_type === Freelancer::class) {
                        $this->availabilityService->create(
                            Freelancer::find($vacation->vacationer_id),
                            $updateVacationRequest,
                            $this->notificationService,
                            $this->availabilityConflictService,
                            $availabilitySeriesService,
                            $this->changeService,
                            $this->schedulingService
                        );
                    }
                    $this->vacationService->delete($vacation);
                    $conflicts = $vacation->conflicts()->get();
                    foreach ($conflicts as $conflict) {
                        $this->availabilityConflictService->create($conflict->toArray());
                        $conflict->delete();
                    }
                }
            } else {
                $this->vacationService->update(
                    $updateVacationRequest,
                    $vacation,
                    $this->vacationConflictService,
                    $this->notificationService
                );
            }
        }
        return redirect()->back();
    }

    /**
     * Ersetzt einen Eintrag als Ganzes: löscht die Zeile bzw. deren komplette Serie und legt den
     * Eintrag aus dem Request neu an (Einzeltag, Zeitraum als tägliche Serie oder Wiederholung).
     * Deckt damit auch Typwechsel (Abwesenheit -> Verfügbarkeit) und Zeitraumänderungen ab.
     */
    public function updateEntry(
        CreateVacationRequest $createVacationRequest,
        Vacation $vacation,
        AvailabilityConflictService $availabilityConflictService,
        AvailabilitySeriesService $availabilitySeriesService
    ): RedirectResponse {
        $this->authorize('update', $vacation);

        $vacationer = $vacation->vacationer_type::find($vacation->vacationer_id);
        abort_if($vacationer === null, 404);

        DB::transaction(function () use (
            $createVacationRequest,
            $vacation,
            $vacationer,
            $availabilityConflictService,
            $availabilitySeriesService
        ): void {
            $series = $vacation->series_id !== null ? VacationSeries::find($vacation->series_id) : null;
            if ($series !== null) {
                $this->vacationSeriesService->deleteSeries($series);
            } else {
                $this->vacationService->delete($vacation);
            }

            if ($createVacationRequest->type === 'vacation') {
                $this->vacationService->create(
                    $vacationer,
                    $createVacationRequest,
                    $this->vacationConflictService,
                    $this->vacationSeriesService,
                    $this->changeService,
                    $this->schedulingService,
                    $this->notificationService
                );
            } else {
                $this->availabilityService->create(
                    $vacationer,
                    $createVacationRequest,
                    $this->notificationService,
                    $availabilityConflictService,
                    $availabilitySeriesService,
                    $this->changeService,
                    $this->schedulingService
                );
            }
        });

        return redirect()->back();
    }

    public function destroy(Vacation $vacation): RedirectResponse
    {
        $this->authorize('delete', $vacation);
        $this->vacationService->delete($vacation);
        return redirect()->back();
    }

    public function destroySeries(VacationSeries $vacationSeries): RedirectResponse
    {
        $firstVacation = $vacationSeries->vacations()->first();
        if ($firstVacation !== null) {
            $this->authorize('delete', $firstVacation);
        }
        $this->vacationSeriesService->deleteSeries($vacationSeries);
        return redirect()->back();
    }
}

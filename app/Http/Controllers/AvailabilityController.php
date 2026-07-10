<?php

namespace App\Http\Controllers;

use Artwork\Modules\Availability\Https\Requests\UpdateAvailabilityRequest;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Https\Requests\CreateVacationRequest;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Vacation\Services\VacationSeriesService;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly VacationService $vacationService,
        private readonly AvailabilitySeriesService $availabilitySeriesService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly VacationSeriesService $vacationSeriesService,
        private readonly ChangeService $changeService,
        private readonly SchedulingService $schedulingService,
        private readonly NotificationService $notificationService
    ) {
    }

    public function update(
        UpdateAvailabilityRequest $updateAvailabilityRequest,
        Availability $availability,
        AvailabilityConflictService $availabilityConflictService
    ): RedirectResponse {
        $this->authorize('update', $availability);
        if ($updateAvailabilityRequest->validated()) {
            if ($updateAvailabilityRequest->type_before_update !== $updateAvailabilityRequest->type) {
                if ($updateAvailabilityRequest->type === 'vacation') {
                    if ($availability->available_type === User::class) {
                        $this->vacationService->create(
                            User::find($availability->available_id),
                            $updateAvailabilityRequest,
                            $this->vacationConflictService,
                            $this->vacationSeriesService,
                            $this->changeService,
                            $this->schedulingService,
                            $this->notificationService
                        );
                    } elseif ($availability->available_type === Freelancer::class) {
                        $this->vacationService->create(
                            Freelancer::find($availability->available_id),
                            $updateAvailabilityRequest,
                            $this->vacationConflictService,
                            $this->vacationSeriesService,
                            $this->changeService,
                            $this->schedulingService,
                            $this->notificationService
                        );
                    }
                    $this->availabilityService->delete($availability);
                }
                return redirect()->back();
            } else {
                $this->availabilityService->update(
                    $updateAvailabilityRequest,
                    $availability,
                    $this->notificationService,
                    $availabilityConflictService
                );
            }
        }
        return redirect()->back();
    }

    /**
     * Ersetzt einen Eintrag als Ganzes: löscht die Zeile bzw. deren komplette Serie und legt den
     * Eintrag aus dem Request neu an (Einzeltag, Zeitraum als tägliche Serie oder Wiederholung).
     * Deckt damit auch Typwechsel (Verfügbarkeit -> Abwesenheit) und Zeitraumänderungen ab.
     */
    public function updateEntry(
        CreateVacationRequest $createVacationRequest,
        Availability $availability,
        AvailabilityConflictService $availabilityConflictService
    ): RedirectResponse {
        $this->authorize('update', $availability);

        $available = $availability->available_type::find($availability->available_id);
        abort_if($available === null, 404);

        DB::transaction(function () use (
            $createVacationRequest,
            $availability,
            $available,
            $availabilityConflictService
        ): void {
            $series = $availability->series_id !== null
                ? AvailabilitySeries::find($availability->series_id)
                : null;
            if ($series !== null) {
                $this->availabilitySeriesService->deleteSeries($series);
            } else {
                $this->availabilityService->delete($availability);
            }

            if ($createVacationRequest->type === 'vacation') {
                $this->vacationService->create(
                    $available,
                    $createVacationRequest,
                    $this->vacationConflictService,
                    $this->vacationSeriesService,
                    $this->changeService,
                    $this->schedulingService,
                    $this->notificationService
                );
            } else {
                $this->availabilityService->create(
                    $available,
                    $createVacationRequest,
                    $this->notificationService,
                    $availabilityConflictService,
                    $this->availabilitySeriesService,
                    $this->changeService,
                    $this->schedulingService
                );
            }
        });

        return redirect()->back();
    }

    public function destroy(Availability $availability): RedirectResponse
    {
        $this->authorize('delete', $availability);
        $this->availabilityService->delete($availability);
        return redirect()->back();
    }

    public function destroySeries(AvailabilitySeries $availabilitySeries): RedirectResponse
    {
        $firstAvailability = $availabilitySeries->availabilities()->first();
        if ($firstAvailability !== null) {
            $this->authorize('delete', $firstAvailability);
        }
        $this->availabilitySeriesService->deleteSeries($availabilitySeries);
        return redirect()->back();
    }
}

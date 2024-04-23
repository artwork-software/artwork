<?php

namespace App\Http\Controllers;

use App\Models\Freelancer;
use App\Models\User;
use App\Support\Services\NotificationService;
use Artwork\Modules\Availability\Https\Requests\UpdateAvailabilityRequest;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Artwork\Modules\Vacation\Services\VacationSeriesService;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Http\RedirectResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly VacationService $vacationService,
        private readonly AvailabilitySeriesService $availabilitySeriesService,
        private readonly VacationConflictService $vacationConflictService,
        private readonly VacationSeriesService $vacationSeriesService,
        private readonly ChangeService $changeService,
        private readonly SchedulingController $schedulingController,
        private readonly NotificationService $notificationService
    ) {
    }

    public function update(
        UpdateAvailabilityRequest $updateAvailabilityRequest,
        Availability $availability,
        AvailabilityConflictService $availabilityConflictService
    ): RedirectResponse {
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
                            $this->schedulingController,
                            $this->notificationService
                        );
                    } elseif ($availability->available_type === Freelancer::class) {
                        $this->vacationService->create(
                            Freelancer::find($availability->available_id),
                            $updateAvailabilityRequest,
                            $this->vacationConflictService,
                            $this->vacationSeriesService,
                            $this->changeService,
                            $this->schedulingController,
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

    public function destroy(Availability $availability): RedirectResponse
    {
        $this->availabilityService->delete($availability);
        return redirect()->back();
    }

    public function destroySeries(AvailabilitySeries $availabilitySeries): RedirectResponse
    {
        $this->availabilitySeriesService->deleteSeries($availabilitySeries);
        return redirect()->back();
    }
}

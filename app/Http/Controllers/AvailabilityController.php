<?php

namespace App\Http\Controllers;

use App\Models\Freelancer;
use App\Models\User;
use Artwork\Modules\Availability\Https\Requests\UpdateAvailabilityRequest;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Models\AvailabilitySeries;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Http\RedirectResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly VacationService $vacationService,
        private readonly AvailabilitySeriesService $availabilitySeriesService
    ) {
    }

    public function update(
        UpdateAvailabilityRequest $updateAvailabilityRequest,
        Availability $availability
    ): RedirectResponse {
        if ($updateAvailabilityRequest->validated()) {
            if ($updateAvailabilityRequest->type_before_update !== $updateAvailabilityRequest->type) {
                if ($updateAvailabilityRequest->type === 'vacation') {
                    if ($availability->available_type === User::class) {
                        $this->vacationService->create(
                            vacationer: User::find($availability->available_id),
                            data: $updateAvailabilityRequest
                        );
                    } elseif ($availability->available_type === Freelancer::class) {
                        $this->vacationService->create(
                            vacationer: Freelancer::find($availability->available_id),
                            data: $updateAvailabilityRequest
                        );
                    }
                    $this->availabilityService->delete($availability);
                }
                return redirect()->back();
            } else {
                $this->availabilityService->update(
                    data: $updateAvailabilityRequest,
                    availability: $availability
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

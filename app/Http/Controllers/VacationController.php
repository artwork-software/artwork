<?php

namespace App\Http\Controllers;

use App\Models\Freelancer;
use App\Models\User;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Vacation\Https\Requests\CreateVacationRequest;
use Artwork\Modules\Vacation\Https\Requests\UpdateVacationRequest;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\VacationSeries;
use Artwork\Modules\Vacation\Services\VacationSeriesService;
use Artwork\Modules\Vacation\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    public function __construct(
        private readonly VacationService $vacationService,
        private readonly AvailabilityService $availabilityService,
        private readonly VacationSeriesService $vacationSeriesService
    ) {
    }

    public function store(CreateVacationRequest $createVacationRequest, User $user): void
    {
        if ($createVacationRequest->type === 'vacation') {
            $this->vacationService->create(
                $user,
                $createVacationRequest
            );
        } else {
            $this->availabilityService->create(
                $user,
                $createVacationRequest
            );
        }
    }

    public function storeFreelancerVacation(CreateVacationRequest $createVacationRequest, Freelancer $freelancer): void
    {
        if ($createVacationRequest->type === 'vacation') {
            $this->vacationService->create(
                $freelancer,
                $createVacationRequest
            );
        } else {
            $this->availabilityService->create(
                $freelancer,
                $createVacationRequest
            );
        }
    }

    public function checkVacation(Request $request, User $user): void
    {
        $day = Carbon::parse($request->day);
        if ($request->checked) {
            $this->vacationService->deleteVacationInterval($user, $day, $day);
            return;
        }
        $vacations = $this->vacationService->findVacationWithinInterval($user, $day, $day);
        if ($vacations->count() === 0) {
            $this->vacationService->create($user, $day, $day);
        }

        $shifts = $user->shifts()->where('event_start_day', $day)->get();
        foreach ($shifts as $shift) {
            $shift->users()->detach($user->id);
        }
    }

    public function update(UpdateVacationRequest $updateVacationRequest, Vacation $vacation): \Illuminate\Http\RedirectResponse
    {
        if ($updateVacationRequest->validated()) {
            if ($updateVacationRequest->type_before_update !== $updateVacationRequest->type) {
                if ($updateVacationRequest->type === 'available') {
                    if ($vacation->vacationer_type === User::class) {
                        $this->availabilityService->create(
                            available: User::find($vacation->vacationer_id),
                            data: $updateVacationRequest
                        );
                    } elseif ($vacation->vacationer_type === Freelancer::class) {
                        $this->availabilityService->create(
                            available: Freelancer::find($vacation->vacationer_id),
                            data: $updateVacationRequest
                        );
                    }
                    $this->vacationService->delete($vacation);
                }
                return redirect()->back();
            } else {
                $this->vacationService->update(
                    data: $updateVacationRequest,
                    vacation: $vacation
                );
            }
        }
        return redirect()->back();
    }

    public function destroy(Vacation $vacation): \Illuminate\Http\RedirectResponse
    {
        $this->vacationService->delete($vacation);
        return redirect()->back();
    }

    public function destroySeries(VacationSeries $vacationSeries): void
    {
        $this->vacationSeriesService->deleteSeries($vacationSeries);
    }
}

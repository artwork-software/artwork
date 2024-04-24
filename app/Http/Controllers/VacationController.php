<?php

namespace App\Http\Controllers;

use Artwork\Modules\Availability\Services\AvailabilityConflictService;
use Artwork\Modules\Availability\Services\AvailabilitySeriesService;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Scheduling\Services\SchedulingService;
use Artwork\Modules\User\Models\User;
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
        private readonly NotificationService $notificationService
    ) {
    }

    public function store(
        CreateVacationRequest $createVacationRequest,
        User $user,
        AvailabilityConflictService $availabilityConflictService,
        AvailabilitySeriesService $availabilitySeriesService
    ): void {
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
        $checked = $request->checked;

        if ($checked) {
            $this->vacationService->deleteVacationInterval($user, $day);
            return;
        }

        $vacations = $this->vacationService->findVacationWithinInterval($user, $day);
        if ($vacations->count() === 0) {
            $createVacationRequest = new CreateVacationRequest([
                'date' => $day,
                'type' => 'vacation',
                'full_day' => true,
                'is_series' => false,
                'comment' => '',
            ]);
            $this->vacationService->create(
                $user,
                $createVacationRequest,
                $this->vacationConflictService,
                $this->vacationSeriesService,
                $this->changeService,
                $this->schedulingService,
                $this->notificationService
            );
        }

        $shifts = $user->shifts()->where('event_start_day', $day)->get();
        foreach ($shifts as $shift) {
            $shift->users()->detach($user->id);
        }
    }

    public function checkVacationFreelancer(
        Request $request,
        Freelancer $freelancer
    ): void {
        $day = Carbon::parse($request->day)->format('Y-m-d');
        $checked = $request->checked;

        if ($checked) {
            $this->vacationService->deleteVacationInterval($freelancer, $day);
            return;
        }

        $vacations = $this->vacationService->findVacationWithinInterval($freelancer, $day);
        if ($vacations->count() === 0) {
            $createVacationRequest = new CreateVacationRequest([
                'date' => $day,
                'type' => 'vacation',
                'full_day' => true,
                'is_series' => false,
                'comment' => '',
            ]);
            $this->vacationService->create(
                $freelancer,
                $createVacationRequest,
                $this->vacationConflictService,
                $this->vacationSeriesService,
                $this->changeService,
                $this->schedulingService,
                $this->notificationService
            );
        }

        $shifts = $freelancer->shifts()->where('event_start_day', $day)->get();
        foreach ($shifts as $shift) {
            $shift->freelancer()->detach($freelancer->id);
        }
    }

    public function update(
        UpdateVacationRequest $updateVacationRequest,
        Vacation $vacation,
        AvailabilitySeriesService $availabilitySeriesService
    ): RedirectResponse {
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

    public function destroy(Vacation $vacation): RedirectResponse
    {
        $this->vacationService->delete($vacation);
        return redirect()->back();
    }

    public function destroySeries(VacationSeries $vacationSeries): void
    {
        $this->vacationSeriesService->deleteSeries($vacationSeries);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Freelancer;
use App\Models\User;
use Artwork\Modules\Availability\Https\Requests\UpdateAvailabilityRequest;
use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly VacationService $vacationService
    ) {
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): \Illuminate\Http\Response
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): \Illuminate\Http\Response
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): \Illuminate\Http\Response
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \Artwork\Modules\Availability\Models\Availability  $availability
     * @return \Illuminate\Http\Response
     */
    public function show(Availability $availability): \Illuminate\Http\Response
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Artwork\Modules\Availability\Models\Availability  $availability
     * @return \Illuminate\Http\Response
     */
    public function edit(Availability $availability): \Illuminate\Http\Response
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Artwork\Modules\Availability\Models\Availability  $availability
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateAvailabilityRequest $updateAvailabilityRequest, Availability $availability): \Illuminate\Http\RedirectResponse
    {
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Artwork\Modules\Availability\Models\Availability  $availability
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Availability $availability): \Illuminate\Http\RedirectResponse
    {
        $this->availabilityService->delete($availability);
        return redirect()->back();
    }
}

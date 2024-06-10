<?php

namespace App\Http\Controllers;

use Artwork\Modules\DayService\Http\Requests\CreateDayServiceRequest;
use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\DayService\Models\DayServiceable;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class DayServiceController extends Controller
{

    public function __construct(
        private readonly DayServicesService $dayServicesService
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('Settings/DayServiceIndex', [
            'dayServices' => $this->dayServicesService->getAll()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateDayServiceRequest $request): void
    {
        $this->dayServicesService->create($request->only(['name', 'icon', 'hex_color']));
    }

    /**
     * Display the specified resource.
     */
    public function show(DayService $dayService): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DayService $dayService): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreateDayServiceRequest $request, DayService $dayService): void
    {
        $this->dayServicesService->update($dayService, $request->only(['name', 'icon', 'hex_color']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DayService $dayService): void
    {
        //
    }

    public function attachDayServiceable(
        DayService $dayService,
        int $dayServiceable,
        Request $request
    ): void {
        $modelInstance = $this->dayServicesService->findModelInstance($request->modelType, $dayServiceable);
        $this->dayServicesService->attachDayServiceable($dayService, $modelInstance, $request->date);
    }



    public function removeDayServiceable(
        int $dayServiceable,
        Request $request
    ): void {
        $modelInstance = $this->dayServicesService->findModelInstance($request->modelType, $dayServiceable);
        $modelInstance->dayServices()->wherePivot('date', $request->date)->detach($request->dayService);
    }
}

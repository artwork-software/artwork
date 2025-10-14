<?php

namespace App\Http\Controllers;

use Artwork\Modules\DayService\Http\Requests\CreateDayServiceRequest;
use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Illuminate\Http\Request;

class DayServiceController extends Controller
{

    public function __construct(
        private readonly DayServicesService $dayServicesService,
    ) {
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('Settings/DayServiceIndex', [
            'dayServices' => $this->dayServicesService->getAll(),
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
        $this->dayServicesService->delete($dayService);
    }

    /**
     * @return array<string, int|array<int, array<string, mixed>>>
     */
    public function attachDayServiceable(
        DayService $dayService,
        int $dayServiceable,
        Request $request,
    ): array {
        $modelInstance = $this->dayServicesService->findModelInstance(
            $modelType = $request->string('modelType'),
            $dayServiceable
        );

        $this->dayServicesService->attachDayServiceable(
            $dayService,
            $modelInstance,
            $request->string('date')
        );

        $dayServicesPerDay = [];
        foreach ($modelInstance->getAttribute('dayServices') as $dayService) {
            $dayServicesPerDay[$dayService->getAttribute('pivot')->getAttribute('date')][] = $dayService;
        }

        return [
            'id' => $modelInstance->getAttribute('id'),
            'type' => $modelType,
            'dayServices' => $dayServicesPerDay,
        ];
    }

    /**
     * @return array<string, int|array<int, array<string, mixed>>>
     */
    public function removeDayServiceable(
        int $dayServiceable,
        Request $request,
    ): array {
        $modelInstance = $this->dayServicesService->findModelInstance(
            $modelType = $request->string('modelType'),
            $dayServiceable
        );

        $modelInstance->dayServices()->wherePivot(
            'date',
            $request->string('date')
        )->detach($request->integer('dayService'));

        $dayServicesPerDay = [];
        /** @var DayService $dayService */
        foreach ($modelInstance->getAttribute('dayServices') as $dayService) {
            $dayServicesPerDay[$dayService->getAttribute('pivot')->getAttribute('date')][] = $dayService;
        }

        return [
            'id' => $modelInstance->getAttribute('id'),
            'type' => $modelType,
            'dayServices' => $dayServicesPerDay,
        ];
    }
}

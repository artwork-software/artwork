<?php

namespace App\Http\Controllers;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Holidays\Requests\HolidayRequest;
use Artwork\Modules\Holidays\Services\HolidayFrontendService;
use Artwork\Modules\Holidays\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function __construct(
        private readonly HolidayFrontendService $holidayFrontendService,
        private readonly HolidayService $holidayService
    ) {
    }

    public function index(): JsonResponse
    {
        $dto = [];
        foreach ($this->holidayService->getAll(['subdivisions']) as $holiday) {
            $dto[] = $this->holidayFrontendService->createShowDto($holiday)->toArray();
        }

        return response()->json($dto);
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        $holiday->delete();
        return response()->json([''], Response::HTTP_NO_CONTENT);
    }

    public function show(Holiday $holiday): JsonResponse
    {
        return response()->json($this->holidayFrontendService->createShowDto($holiday)->toArray());
    }

    public function store(HolidayRequest $request): JsonResponse
    {
        $subdivisions = [];
        foreach ($request->input('subdivisions') as $subdivision) {
            $subdivisions[] = Subdivision::find($subdivision);
        }
        $holiday = $this->holidayService->create(
            $request->input('name'),
            $subdivisions,
            $request->input('date'),
            $request->input('country'),
            $request->input('rota')
        );

        return response()->json(
            $this->holidayFrontendService->createShowDto($holiday)->toArray(),
            Response::HTTP_CREATED
        );
    }

    public function update(HolidayRequest $request, Holiday $holiday): JsonResponse
    {
        $subdivisions = [];
        foreach ($request->input('subdivisions') as $subdivision) {
            $subdivisions[] = Subdivision::find($subdivision);
        }
        $holiday->fill($request->only(['name', 'date', 'rota', 'country']));
        $holiday->subdivisions()->sync($subdivisions);
        $holiday = $this->holidayService->save($holiday);

        return response()->json($this->holidayFrontendService->createShowDto($holiday)->toArray(), Response::HTTP_OK);
    }
}

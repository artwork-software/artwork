<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppDateRangeRequest;
use Artwork\Modules\AppApi\Services\AppCalendarService;
use Illuminate\Http\JsonResponse;

class AppCalendarController extends Controller
{
    public function __construct(
        private readonly AppCalendarService $calendarService,
    ) {
    }

    /**
     * Parity with the web calendar: no dedicated view permission exists, any
     * authenticated user may see the room occupancy calendar.
     */
    public function show(AppDateRangeRequest $request): JsonResponse
    {
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        return response()->json([
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
            'days' => $this->calendarService->getDays($startDate, $endDate),
        ]);
    }
}

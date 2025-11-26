<?php

namespace App\Http\Controllers;

use Artwork\Core\Services\HelperService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class HelperController extends Controller
{
    public function __construct(
        protected HelperService $helperService
    ) {
    }

    public function getDateRangeByCalendarWeekAndYear(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            [$start, $end] = $this->helperService->getDateRangeByCalendarWeekAndYear(
                $request->get('week_number'),
                $request->get('year')
            );
            return response()->json([
                'start_date' => $start->translatedFormat('d F Y'),
                'end_date'   => $end->translatedFormat('d F Y'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}

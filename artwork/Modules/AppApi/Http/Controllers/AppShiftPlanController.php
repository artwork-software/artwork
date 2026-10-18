<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppDateRangeRequest;
use Artwork\Modules\AppApi\Services\AppShiftPlanService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppShiftPlanController extends Controller
{
    public function __construct(
        private readonly AppShiftPlanService $shiftPlanService,
    ) {
    }

    public function show(AppDateRangeRequest $request): JsonResponse
    {
        $this->authorize(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);

        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $days = $this->shiftPlanService->getDays($request->user(), $startDate, $endDate);

        return response()->json([
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
            'days' => $days,
            'totals' => $this->shiftPlanService->totals($days),
        ]);
    }

    public function showShift(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize(PermissionEnum::CAN_VIEW_OWN_ROSTER->value);

        $entry = $this->shiftPlanService->shiftOf($request->user(), $shift);
        abort_if($entry === null, 404);

        return response()->json($entry);
    }
}

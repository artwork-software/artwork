<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Http\Requests\AppDateRangeRequest;
use Artwork\Modules\AppApi\Services\AppShiftListService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Illuminate\Http\JsonResponse;

class AppShiftListController extends Controller
{
    public function __construct(
        private readonly AppShiftListService $shiftListService,
    ) {
    }

    public function show(AppDateRangeRequest $request): JsonResponse
    {
        // Same gate as the web Dienstplan-Listenansicht (routes/web.php shifts/list-view).
        $this->authorize(PermissionEnum::VIEW_SHIFT_PLAN->value);

        $startDate = $request->startDate();
        $endDate = $request->endDate();

        return response()->json([
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d'),
            'days' => $this->shiftListService->getDays($request->user(), $startDate, $endDate),
        ]);
    }
}

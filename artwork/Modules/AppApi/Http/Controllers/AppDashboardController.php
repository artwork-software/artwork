<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AppApi\Services\AppDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppDashboardController extends Controller
{
    public function __construct(
        private readonly AppDashboardService $dashboardService,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->getDashboard($request->user()),
        );
    }
}

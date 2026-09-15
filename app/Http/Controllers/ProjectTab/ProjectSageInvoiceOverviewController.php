<?php

namespace App\Http\Controllers\ProjectTab;

use App\Http\Controllers\Controller;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabSageInvoiceOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectSageInvoiceOverviewController extends Controller
{
    public function __construct(
        private readonly ProjectTabSageInvoiceOverviewService $projectTabSageInvoiceOverviewService,
    ) {
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        return response()->json(
            $this->projectTabSageInvoiceOverviewService->buildPayload($project, $request->user())
        );
    }
}

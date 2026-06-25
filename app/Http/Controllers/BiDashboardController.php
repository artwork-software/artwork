<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Services\BiDashboardService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BiDashboardController extends Controller
{
    public function __construct(
        private readonly BiDashboardService $biDashboardService,
        private readonly ProjectTabService $projectTabService
    ) {
    }

    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()->can(PermissionEnum::BI_DASHBOARD->value) || $request->user()->hasRole('admin'),
            403
        );

        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $data = $this->biDashboardService->getDashboardData(
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null
        );

        return Inertia::render('BusinessIntelligence/Dashboard', [
            'dashboard' => $data,
            'firstProjectTabId' => $this->projectTabService->getDefaultOrFirstProjectTab()?->getAttribute('id'),
        ]);
    }
}

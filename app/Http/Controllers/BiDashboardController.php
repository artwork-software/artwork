<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Services\BiDashboardService;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectTabService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BiDashboardController extends Controller
{
    public function __construct(
        private readonly BiDashboardService $biDashboardService,
        private readonly BiExportService $biExportService,
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
            'compare_from' => ['nullable', 'date'],
            'compare_to' => ['nullable', 'date', 'after_or_equal:compare_from'],
            'compare' => ['nullable', 'in:none'],
            // Sparten-Filter als echter Seitenfilter (Kacheln, Charts, Tabelle)
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $this->biDashboardService->getDashboardData(
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
            $validated['compare_from'] ?? null,
            $validated['compare_to'] ?? null,
            ($validated['compare'] ?? null) === 'none',
            $validated['category'] ?? null
        );

        $biTab = $this->projectTabService->findFirstProjectTabWithBusinessIntelligenceComponent();

        $canExport = $request->user()->can(PermissionEnum::BI_EXPORT->value)
            || $request->user()->hasRole('admin');

        return Inertia::render('BusinessIntelligence/Dashboard', [
            'dashboard' => $data,
            'firstProjectTabId' => $biTab?->getAttribute('id')
                ?? $this->projectTabService->getDefaultOrFirstProjectTab()?->getAttribute('id'),
            'biComponentInTab' => $biTab !== null,
            'exportOptions' => $canExport ? $this->buildExportOptions() : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExportOptions(): array
    {
        return array_merge($this->biExportService->exportConfigurationOptions(), [
            'projects' => Project::query()
                ->where('is_group', false)
                ->orderBy('name')
                ->get(['id', 'name', 'cost_center_id']),
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}

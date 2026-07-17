<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Http\Requests\BiExportCacheRequest;
use Artwork\Modules\BusinessIntelligence\Jobs\GenerateBiExportJob;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BiExportController extends Controller
{
    public function __construct(
        private readonly BiExportService $biExportService
    ) {
    }

    public function index(GeneralSettings $generalSettings): Response
    {
        abort_unless(
            auth()->user()->can(PermissionEnum::BI_EXPORT->value) || auth()->user()->hasRole('admin'),
            403
        );

        $options = $this->biExportService->exportConfigurationOptions();

        $projects = Project::query()
            ->where('is_group', false)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Settings/BiSettings/Export', [
            'projects' => $projects,
            'columns' => $options['columns'],
            'tagColumns' => $options['tagColumns'],
            'customFieldColumns' => $options['customFieldColumns'],
            'presets' => $options['presets'],
            'defaultDateFrom' => $generalSettings->playing_time_window_start ?: null,
            'defaultDateTo' => $generalSettings->playing_time_window_end ?: null,
        ]);
    }

    public function cacheExportConfiguration(BiExportCacheRequest $request): JsonResponse
    {
        $token = $this->biExportService->cacheExportConfiguration($request->validated());

        // Run synchronously so the export is reliably generated even without a running
        // queue worker. Switch to ::dispatch() for true async once a worker processes
        // the configured ("database") queue.
        GenerateBiExportJob::dispatchSync($token);

        return response()->json(['token' => $token]);
    }

    public function status(string $cacheToken): JsonResponse
    {
        return response()->json($this->biExportService->getStatus($cacheToken));
    }

    public function download(string $cacheToken): BinaryFileResponse
    {
        return $this->biExportService->downloadStored($cacheToken);
    }
}

<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Http\Requests\BiExportCacheRequest;
use Artwork\Modules\BusinessIntelligence\Jobs\GenerateBiExportJob;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Models\BiExportPreset;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
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

        $columns = collect(BiExportService::columnLabelMap())
            ->map(fn(string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values();

        $tags = BiEventTypeTag::query()
            ->get(['id', 'name', 'name_de'])
            ->map(fn(BiEventTypeTag $tag) => [
                'key' => 'tag_' . $tag->id,
                'label' => $tag->name_de ?: $tag->name,
            ]);

        $customFields = Component::isBiField()
            ->orderBy('bi_order')
            ->get(['id', 'name'])
            ->map(fn(Component $component) => [
                'key' => 'custom_field_' . $component->id,
                'label' => $component->name,
            ]);

        $projects = Project::query()
            ->where('is_group', false)
            ->orderBy('name')
            ->get(['id', 'name']);

        $presets = BiExportPreset::query()->orderBy('name')->get(['id', 'name', 'columns', 'is_shared']);

        return Inertia::render('Settings/BiSettings/Export', [
            'projects' => $projects,
            'columns' => $columns,
            'tagColumns' => $tags,
            'customFieldColumns' => $customFields,
            'presets' => $presets,
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

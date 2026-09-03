<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Http\Requests\BiExportCacheRequest;
use Artwork\Modules\BusinessIntelligence\Jobs\GenerateBiExportJob;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\CostCenter\Models\CostCenter;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Role\Enums\RoleEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BiExportController extends Controller
{
    public function __construct(
        private readonly BiExportService $biExportService
    ) {
    }

    /**
     * Alles, was der gemeinsame Export-Dialog braucht (Projekt-Tab UND Dashboard):
     * Spaltenkatalog in Gruppen, Presets, Produktionen, Kostenträger, Spielzeit.
     */
    public function options(Request $request, GeneralSettings $generalSettings): JsonResponse
    {
        $this->authorizeBiExport($request);

        $user = $request->user();
        $options = $this->biExportService->exportConfigurationOptions(
            $user->id,
            $user->hasRole(RoleEnum::ARTWORK_ADMIN->value)
        );

        return response()->json(array_merge($options, [
            'projects' => Project::query()
                ->where('is_group', false)
                ->orderBy('name')
                ->get(['id', 'name', 'cost_center_id']),
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name']),
            'seasonFrom' => $generalSettings->playing_time_window_start ?: null,
            'seasonTo' => $generalSettings->playing_time_window_end ?: null,
        ]));
    }

    public function cacheExportConfiguration(BiExportCacheRequest $request): JsonResponse
    {
        $user = $request->user();
        $token = $this->biExportService->cacheExportConfiguration(array_merge($request->validated(), [
            // Fürs Info-Blatt der Datei
            'user_id' => $user->id,
            'user_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
        ]));

        GenerateBiExportJob::dispatch($token);

        return response()->json(['token' => $token]);
    }

    public function status(string $cacheToken): JsonResponse
    {
        return response()->json($this->biExportService->getStatus($cacheToken));
    }

    public function download(string $cacheToken): BinaryFileResponse|RedirectResponse
    {
        return $this->biExportService->downloadStored($cacheToken);
    }

    private function authorizeBiExport(Request $request): void
    {
        abort_unless(
            $request->user()->can(PermissionEnum::BI_EXPORT->value)
                || $request->user()->hasRole(RoleEnum::ARTWORK_ADMIN->value),
            403
        );
    }
}

<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Jobs\GenerateBiBudgetExportJob;
use Artwork\Modules\BusinessIntelligence\Services\BiBudgetExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Projektunabhängiger Budget-Export aus dem BI-Dashboard (Baustein G).
 * Alle Routen sind per 'can:can export bi data' gegated (Route-Gruppe).
 */
class BiBudgetExportController extends Controller
{
    public function __construct(
        private readonly BiBudgetExportService $biBudgetExportService
    ) {
    }

    public function options(): JsonResponse
    {
        return response()->json($this->biBudgetExportService->exportOptions());
    }

    public function matchCounts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prefix' => ['required', 'string', 'max:100'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'cost_center_ids' => ['nullable', 'array'],
            'cost_center_ids.*' => ['integer', 'exists:cost_centers,id'],
        ]);

        return response()->json($this->biBudgetExportService->matchCounts(
            $validated['prefix'],
            $validated['date_from'],
            $validated['date_to'],
            $validated['cost_center_ids'] ?? []
        ));
    }

    public function cacheExportConfiguration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'cost_center_ids' => ['nullable', 'array'],
            'cost_center_ids.*' => ['integer', 'exists:cost_centers,id'],
            'filters' => ['nullable', 'array'],
            'filters.*.column' => ['required', 'in:kto,kst'],
            'filters.*.prefix' => ['required', 'string', 'max:100'],
            'restrict_bookings_to_range' => ['nullable', 'boolean'],
        ]);

        $token = $this->biBudgetExportService->cacheExportConfiguration($validated);

        GenerateBiBudgetExportJob::dispatch($token);

        return response()->json(['token' => $token]);
    }

    public function status(string $cacheToken): JsonResponse
    {
        return response()->json($this->biBudgetExportService->getStatus($cacheToken));
    }

    public function download(string $cacheToken): BinaryFileResponse
    {
        return $this->biBudgetExportService->downloadStored($cacheToken);
    }
}

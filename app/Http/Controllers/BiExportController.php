<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Http\Requests\BiExportCacheRequest;
use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BiExportController extends Controller
{
    public function __construct(
        private readonly BiExportService $biExportService
    ) {
    }

    public function cacheExportConfiguration(BiExportCacheRequest $request): JsonResponse
    {
        $token = $this->biExportService->cacheExportConfiguration($request->validated());

        return response()->json(['token' => $token]);
    }

    public function download(string $cacheToken): BinaryFileResponse
    {
        return $this->biExportService->download($cacheToken);
    }
}

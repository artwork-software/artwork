<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiExportData;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiExportRequest;
use Artwork\Modules\AiAssistant\Services\AiExportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF-Export einer Tabelle fuer artwork-ai (core-ai-event-stats-exports).
 */
class AiExportApiController extends Controller
{
    public function __construct(private readonly AiExportService $service)
    {
    }

    /**
     * Antwort 201 wie bei den Conversations — ausdruecklich, damit der Contract den Status traegt.
     */
    public function store(StoreAiExportRequest $request): JsonResponse
    {
        return response()->json($this->create($request), Response::HTTP_CREATED);
    }

    private function create(StoreAiExportRequest $request): AiExportData
    {
        return $this->service->create(
            $request->user(),
            (string) $request->validated('title'),
            $request->validated('subtitle'),
            $request->validated('note'),
            $request->columns(),
            $request->rows(),
        );
    }
}

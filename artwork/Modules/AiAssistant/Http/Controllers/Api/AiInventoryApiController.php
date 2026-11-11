<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiInventoryListData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiInventoryRequest;
use Artwork\Modules\AiAssistant\Services\AiInventoryReadService;

/**
 * Inventarverfuegbarkeit fuer artwork-ai (core-api-inventory; Konzept B1 §3).
 */
class AiInventoryApiController extends Controller
{
    public function __construct(private readonly AiInventoryReadService $service)
    {
    }

    public function __invoke(IndexAiInventoryRequest $request): AiInventoryListData
    {
        return $this->service->availability(
            $request->user(),
            $request->from(),
            $request->to(),
            $request->validated('q'),
            $request->validated('limit'),
        );
    }
}

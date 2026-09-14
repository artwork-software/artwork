<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiSessionRequest;
use Artwork\Modules\AiAssistant\Services\AiSessionService;
use Illuminate\Http\JsonResponse;

/**
 * POST /ai/session — Ausstellung des kurzlebigen KI-Session-Tokens (contracts/token-contract.md).
 *
 * Laeuft unter dem Web-Guard: Wer ein Token will, muss eine gueltige Sitzung in artwork haben.
 */
class AiSessionController extends Controller
{
    public function __construct(private readonly AiSessionService $service)
    {
    }

    public function store(StoreAiSessionRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->issue($request->user(), $request->validated('scopes', []))
        );
    }
}

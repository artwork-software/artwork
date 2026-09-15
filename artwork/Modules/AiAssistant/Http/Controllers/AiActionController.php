<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\Services\AiActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /ai/actions/{action}/cancel — Ablehnung aus der Bar (core-confirm-actions; Konzept W2 §6).
 *
 * Web-Guard: die Ablehnung kommt vom Menschen, nicht vom Modell. Es entsteht kein Token und es
 * geht kein Aufruf an artwork-ai.
 */
class AiActionController extends Controller
{
    public function __construct(private readonly AiActionService $actions)
    {
    }

    public function cancel(Request $request, string $action): JsonResponse
    {
        $turn = $this->actions->cancel($request->user(), $action);

        return response()->json(['status' => $turn->status]);
    }
}

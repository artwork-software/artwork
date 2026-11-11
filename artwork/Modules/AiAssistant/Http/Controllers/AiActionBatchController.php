<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\Services\AiActionBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /ai/actions/batches/{batch}/revert — „Rueckgaengig" aus der Bar (core-undo; Konzept W3 §2).
 *
 * Web-Guard: die Ruecknahme loest der Mensch aus, kein Modell ist beteiligt.
 */
class AiActionBatchController extends Controller
{
    public function __construct(private readonly AiActionBatchService $batches)
    {
    }

    public function revert(Request $request, string $batch): JsonResponse
    {
        $reverted = $this->batches->revert($request->user(), $batch);

        return response()->json([
            'id' => $reverted->id,
            'status' => $reverted->status,
            'reverted_at' => $reverted->reverted_at?->toIso8601String(),
            'items' => $reverted->items->count(),
        ]);
    }
}

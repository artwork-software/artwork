<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiPendingActionData;
use Artwork\Modules\AiAssistant\Services\AiActionService;
use Illuminate\Http\Request;

/**
 * GET /api/v1/ai/actions/{action} — die bestaetigte Aktion fuer den Confirm-Turn
 * (core-confirm-actions; Konzept W2 §4).
 *
 * artwork-ai holt hier genau die Argumente, die der Mensch in der Vorschau gesehen hat, und ruft
 * damit den Schreib-Endpunkt auf. Eigentuemerschaft wird direkt geprueft, nicht ueber ein Gate.
 */
class AiActionApiController extends Controller
{
    public function __construct(private readonly AiActionService $actions)
    {
    }

    public function __invoke(Request $request, string $action): AiPendingActionData
    {
        return AiPendingActionData::fromPayload($this->actions->payloadFor($request->user(), $action));
    }
}

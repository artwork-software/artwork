<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiConversationData;
use Artwork\Modules\AiAssistant\DTOs\AiConversationTurnData;
use Artwork\Modules\AiAssistant\DTOs\AiConversationWithTurnsData;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiConversationRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiConversationTurnRequest;
use Artwork\Modules\AiAssistant\Services\AiConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chat-Historie fuer artwork-ai (core-chat-historie; Konzept C3 §5).
 *
 * Laeuft unter auth:api mit dem Scope ai:transcript. Der Token traegt immer den echten User —
 * artwork-ai hat keinen eigenen Datenzugriff, alle Abfragen sind auf dessen Gespraeche beschraenkt.
 */
class AiConversationApiController extends Controller
{
    public function __construct(private readonly AiConversationService $service)
    {
    }

    public function store(StoreAiConversationRequest $request): JsonResponse
    {
        $conversation = $this->service->create($request->user(), $request->validated('title'));

        return response()->json(
            AiConversationData::fromModel($conversation),
            Response::HTTP_CREATED
        );
    }

    public function show(Request $request, string $conversation): JsonResponse
    {
        $model = $this->service->findForUserOrFail($request->user(), $conversation);

        $limit = (int) $request->query('limit', (string) AiConversationService::DEFAULT_TURN_LIMIT);

        return response()->json(
            AiConversationWithTurnsData::fromModel($model, $this->service->latestTurns($model, $limit))
        );
    }

    public function storeTurn(StoreAiConversationTurnRequest $request, string $conversation): JsonResponse
    {
        $model = $this->service->findForUserOrFail($request->user(), $conversation);

        [$turn, $created] = $this->service->recordTurn($model, $request->validated());

        return response()->json(
            AiConversationTurnData::fromModel($turn),
            $created ? Response::HTTP_CREATED : Response::HTTP_OK
        );
    }
}

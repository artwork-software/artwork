<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiEventTypeListData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomListData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiEventTypesRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiRoomsRequest;
use Artwork\Modules\AiAssistant\Services\AiLookupReadService;

/**
 * Nachschlage-Endpunkte fuer artwork-ai (core-api-lookups; Konzept B1 §3, W1 §1).
 */
class AiLookupApiController extends Controller
{
    public function __construct(private readonly AiLookupReadService $service)
    {
    }

    public function rooms(IndexAiRoomsRequest $request): AiRoomListData
    {
        return $this->service->rooms($request->user(), $request->search(), $request->limit());
    }

    public function eventTypes(IndexAiEventTypesRequest $request): AiEventTypeListData
    {
        return $this->service->eventTypes($request->search(), $request->limit());
    }
}

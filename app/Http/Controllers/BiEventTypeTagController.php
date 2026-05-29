<?php

namespace App\Http\Controllers;

use Artwork\Modules\BusinessIntelligence\Http\Requests\StoreBiEventTypeTagRequest;
use Artwork\Modules\BusinessIntelligence\Http\Requests\UpdateBiEventTypeTagRequest;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Services\BiEventTypeTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiEventTypeTagController extends Controller
{
    public function __construct(
        private readonly BiEventTypeTagService $biEventTypeTagService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->biEventTypeTagService->getAllWithEventTypes());
    }

    public function store(StoreBiEventTypeTagRequest $request): JsonResponse
    {
        $tag = $this->biEventTypeTagService->create($request->validated());

        return response()->json($tag, 201);
    }

    public function update(UpdateBiEventTypeTagRequest $request, BiEventTypeTag $biEventTypeTag): JsonResponse
    {
        $tag = $this->biEventTypeTagService->update($biEventTypeTag, $request->validated());

        return response()->json($tag);
    }

    public function destroy(BiEventTypeTag $biEventTypeTag): JsonResponse
    {
        $this->biEventTypeTagService->delete($biEventTypeTag);

        return response()->json(null, 204);
    }

    public function syncEventTypes(Request $request, BiEventTypeTag $biEventTypeTag): JsonResponse
    {
        $request->validate([
            'event_type_ids' => ['required', 'array'],
            'event_type_ids.*' => ['integer', 'exists:event_types,id'],
        ]);

        $this->biEventTypeTagService->syncEventTypes($biEventTypeTag, $request->input('event_type_ids'));

        return response()->json($biEventTypeTag->load('eventTypes'));
    }
}

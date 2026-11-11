<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiCraftCapacityData;
use Artwork\Modules\AiAssistant\DTOs\AiCraftListData;
use Artwork\Modules\AiAssistant\DTOs\AiOpenShiftListData;
use Artwork\Modules\AiAssistant\DTOs\AiRuleViolationListData;
use Artwork\Modules\AiAssistant\DTOs\AiShiftCandidateListData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiCraftsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiOpenShiftsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiRuleViolationsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiShiftCandidatesRequest;
use Artwork\Modules\AiAssistant\Http\Requests\ShowAiCraftCapacityRequest;
use Artwork\Modules\AiAssistant\Services\AiShiftInsightsService;
use Artwork\Modules\AiAssistant\Support\AiShiftPlanningAccess;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lesende Planungs-Endpunkte aus Erweiterung 2 (ai-shift-planning A–D). Alle verlangen
 * „Schichten planen"; unbekannte oder unsichtbare Schicht/Gewerk → 404, fremdes Gewerk → 403.
 */
class AiShiftInsightsApiController extends Controller
{
    public function __construct(private readonly AiShiftInsightsService $service)
    {
    }

    public function openShifts(IndexAiOpenShiftsRequest $request): AiOpenShiftListData
    {
        return $this->service->openShifts(
            $this->planner($request->user()),
            $request->from(),
            $request->to(),
            $request->craftId(),
            $request->limit(),
        );
    }

    public function violations(IndexAiRuleViolationsRequest $request): AiRuleViolationListData
    {
        return $this->service->violations(
            $this->planner($request->user()),
            $request->from(),
            $request->to(),
            $request->craftId(),
            $request->status(),
            $request->limit(),
        );
    }

    public function candidates(IndexAiShiftCandidatesRequest $request, int $shift): AiShiftCandidateListData
    {
        $planner = $this->planner($request->user());
        $model = Shift::query()->visibleFor($planner)->find($shift);
        abort_if($model === null, Response::HTTP_NOT_FOUND);

        return $this->service->candidates($planner, $model, $request->qualificationId());
    }

    public function crafts(IndexAiCraftsRequest $request): AiCraftListData
    {
        return $this->service->crafts($this->planner($request->user()), $request->search(), $request->limit());
    }

    public function capacity(ShowAiCraftCapacityRequest $request, int $craft): AiCraftCapacityData
    {
        $planner = $this->planner($request->user());
        $model = Craft::query()->find($craft);
        abort_if($model === null, Response::HTTP_NOT_FOUND);

        return $this->service->craftCapacity($planner, $model, $request->weekStart());
    }

    private function planner(?User $user): User
    {
        abort_unless($user !== null && AiShiftPlanningAccess::mayPlanShifts($user), Response::HTTP_FORBIDDEN);

        return $user;
    }
}

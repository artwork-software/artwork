<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiCommonSlotsData;
use Artwork\Modules\AiAssistant\DTOs\AiContractListData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomUtilizationListData;
use Artwork\Modules\AiAssistant\DTOs\AiWorkTimeOverviewData;
use Artwork\Modules\AiAssistant\Http\Requests\FindAiCommonSlotsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiContractsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiRoomUtilizationRequest;
use Artwork\Modules\AiAssistant\Http\Requests\ShowAiWorkTimeOverviewRequest;
use Artwork\Modules\AiAssistant\Services\AiCommonSlotsService;
use Artwork\Modules\AiAssistant\Services\AiContractReadService;
use Artwork\Modules\AiAssistant\Services\AiRoomUtilizationService;
use Artwork\Modules\AiAssistant\Services\AiWorkTimeService;

/**
 * Lesende Auswertungen aus ai-scenarios-4: gemeinsame freie Zeit (S14), Vertraege (S15),
 * Stundenuebersicht (S16), Raumauslastung (S17). Fehlt ein Recht, 403 mit `{"error": "<code>"}`.
 */
class AiScenarioFourReadApiController extends Controller
{
    public function __construct(
        private readonly AiCommonSlotsService $commonSlots,
        private readonly AiContractReadService $contracts,
        private readonly AiWorkTimeService $workTime,
        private readonly AiRoomUtilizationService $utilization,
    ) {
    }

    public function commonFreeSlots(FindAiCommonSlotsRequest $request): AiCommonSlotsData
    {
        return $this->commonSlots->find(
            $request->user(),
            $request->people(),
            $request->from(),
            $request->to(),
            $request->durationMinutes(),
            $request->dayStart(),
            $request->dayEnd(),
            $request->roomId(),
        );
    }

    public function contracts(IndexAiContractsRequest $request): AiContractListData
    {
        return $this->contracts->list(
            $request->user(),
            $request->dueBefore(),
            $request->projectId(),
            $request->search(),
            $request->limit(),
        );
    }

    public function workTimeOverview(ShowAiWorkTimeOverviewRequest $request): AiWorkTimeOverviewData
    {
        return $this->workTime->overview(
            $request->user(),
            $request->monthStart(),
            $request->craftId(),
            $request->sort(),
            $request->limit(),
        );
    }

    public function roomUtilization(IndexAiRoomUtilizationRequest $request): AiRoomUtilizationListData
    {
        return $this->utilization->utilization(
            $request->user(),
            $request->from(),
            $request->to(),
            $request->roomId(),
            $request->limit(),
        );
    }
}

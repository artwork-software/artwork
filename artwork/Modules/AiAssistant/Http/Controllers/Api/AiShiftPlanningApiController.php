<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiShiftSuggestionData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\Http\Requests\RemoveAiShiftAssignmentsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiShiftAssignmentsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\SuggestAiShiftPlanRequest;
use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Artwork\Modules\AiAssistant\Services\AiShiftAssignmentWriteService;
use Artwork\Modules\AiAssistant\Services\AiShiftPlanningService;
use Artwork\Modules\AiAssistant\Services\AiShiftUnassignWriteService;
use Artwork\Modules\AiAssistant\Support\AiShiftPlanningAccess;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Schichtvorschlag, Zuweisung und Austragen fuer artwork-ai (ai-shift-planning, Core 3/4, Erweiterung 2 E).
 */
class AiShiftPlanningApiController extends Controller
{
    public function __construct(
        private readonly AiPeopleService $people,
        private readonly AiShiftPlanningService $planning,
        private readonly AiShiftAssignmentWriteService $writer,
        private readonly AiShiftUnassignWriteService $unassigner,
    ) {
    }

    public function suggest(SuggestAiShiftPlanRequest $request): AiShiftSuggestionData
    {
        $planner = $this->planner($request->user());

        return $this->planning->suggest(
            $planner,
            $this->people->find((string) $request->validated('person.type'), (int) $request->validated('person.id')),
            $request->from(),
            $request->to(),
            $request->options(),
            $request->newShifts(),
        );
    }

    public function assign(StoreAiShiftAssignmentsRequest $request): AiWritePreviewData|AiWriteResultData
    {
        $planner = $this->planner($request->user());

        return $this->writer->assign(
            $planner,
            $this->people->find((string) $request->validated('person.type'), (int) $request->validated('person.id')),
            $request->assignments(),
            $request->newShifts(),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function unassign(RemoveAiShiftAssignmentsRequest $request): AiWritePreviewData|AiWriteResultData
    {
        $planner = $this->planner($request->user());

        return $this->unassigner->unassign(
            $planner,
            $this->people->find((string) $request->validated('person.type'), (int) $request->validated('person.id')),
            $request->shiftIds(),
            AiWriteIntent::fromRequest($request),
        );
    }

    private function planner(?User $user): User
    {
        abort_unless($user !== null && AiShiftPlanningAccess::mayPlanShifts($user), Response::HTTP_FORBIDDEN);

        return $user;
    }
}

<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiReplacementSuggestionData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\Http\Requests\CopyAiShiftsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\ReplaceAiShiftAssignmentsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiAbsenceRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiShiftsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiTaskRequest;
use Artwork\Modules\AiAssistant\Http\Requests\SuggestAiReplacementsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\UpdateAiEventBatchRequest;
use Artwork\Modules\AiAssistant\Services\AiAbsenceWriteService;
use Artwork\Modules\AiAssistant\Services\AiEventWriteService;
use Artwork\Modules\AiAssistant\Services\AiPeopleService;
use Artwork\Modules\AiAssistant\Services\AiReplacementService;
use Artwork\Modules\AiAssistant\Services\AiShiftCreateWriteService;
use Artwork\Modules\AiAssistant\Services\AiShiftReplaceWriteService;
use Artwork\Modules\AiAssistant\Services\AiTaskCreateService;
use Artwork\Modules\AiAssistant\Support\AiShiftPlanningAccess;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ausfaelle, Anlegen und Verschieben (ai-scenarios-4 S9–S13), Core-Seite.
 *
 * Unsichtbare Objekte → 404; fehlt bei einem sichtbaren Objekt ein Recht → 403 (mit
 * `{"error": "<code>"}`, wo der Assistent den Grund nennen soll).
 */
class AiScenarioFourApiController extends Controller
{
    public function __construct(
        private readonly AiPeopleService $people,
        private readonly AiReplacementService $replacements,
        private readonly AiShiftReplaceWriteService $replacer,
        private readonly AiAbsenceWriteService $absences,
        private readonly AiTaskCreateService $tasks,
        private readonly AiEventWriteService $events,
        private readonly AiShiftCreateWriteService $shifts,
    ) {
    }

    public function replacements(SuggestAiReplacementsRequest $request): AiReplacementSuggestionData
    {
        return $this->replacements->suggest(
            $this->planner($request->user()),
            $this->people->find((string) $request->validated('person.type'), (int) $request->validated('person.id')),
            $request->from(),
            $request->to(),
            $request->craftId(),
        );
    }

    public function replace(ReplaceAiShiftAssignmentsRequest $request): AiWritePreviewData|AiWriteResultData
    {
        return $this->replacer->replace(
            $this->planner($request->user()),
            $request->rows(),
            $request->markAbsent(),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function storeAbsence(StoreAiAbsenceRequest $request): AiWritePreviewData|AiWriteResultData
    {
        /** @var User $user */
        $user = $request->user();

        return $this->absences->create(
            $user,
            $this->people->find((string) $request->validated('person.type'), (int) $request->validated('person.id')),
            $request->days(),
            (string) $request->validated('type'),
            $request->times(),
            $request->validated('comment'),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function storeTask(StoreAiTaskRequest $request): AiWritePreviewData|AiWriteResultData
    {
        /** @var User $user */
        $user = $request->user();

        return $this->tasks->create(
            $user,
            (string) $request->validated('name'),
            $request->validated('description'),
            $request->deadlineDay(),
            $request->intOrNull('checklist_id'),
            $request->intOrNull('project_id'),
            $request->assigneeIds(),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function updateEvents(UpdateAiEventBatchRequest $request): AiWritePreviewData|AiWriteResultData
    {
        /** @var User $user */
        $user = $request->user();

        return $this->events->updateBatch(
            $user,
            $request->eventIds(),
            $request->intOrNull('shift_minutes'),
            $request->validated('move_to_day'),
            $request->intOrNull('room_id'),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function storeShifts(StoreAiShiftsRequest $request): AiWritePreviewData|AiWriteResultData
    {
        return $this->shifts->create(
            $this->planner($request->user()),
            (int) $request->validated('event_id'),
            $request->shifts(),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function copyShifts(CopyAiShiftsRequest $request): AiWritePreviewData|AiWriteResultData
    {
        return $this->shifts->copy(
            $this->planner($request->user()),
            (int) $request->validated('source_event_id'),
            (int) $request->validated('target_event_id'),
            $request->intOrNull('craft_id'),
            $request->boolean('with_assignments'),
            AiWriteIntent::fromRequest($request),
        );
    }

    private function planner(?User $user): User
    {
        abort_unless($user !== null && AiShiftPlanningAccess::mayPlanShifts($user), Response::HTTP_FORBIDDEN);

        return $user;
    }
}

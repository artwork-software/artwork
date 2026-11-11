<?php

namespace Artwork\Modules\AiAssistant\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Artwork\Modules\AiAssistant\DTOs\AiBudgetSummaryData;
use Artwork\Modules\AiAssistant\DTOs\AiDayOverviewData;
use Artwork\Modules\AiAssistant\DTOs\AiEventStaffingData;
use Artwork\Modules\AiAssistant\DTOs\AiPersonAbsencesListData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomConflictListData;
use Artwork\Modules\AiAssistant\DTOs\AiTaskListData;
use Artwork\Modules\AiAssistant\DTOs\AiWorkTimeData;
use Artwork\Modules\AiAssistant\DTOs\AiWritePreviewData;
use Artwork\Modules\AiAssistant\DTOs\AiWriteResultData;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiAbsencesRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiMyTasksRequest;
use Artwork\Modules\AiAssistant\Http\Requests\IndexAiRoomConflictsRequest;
use Artwork\Modules\AiAssistant\Http\Requests\ShowAiDayOverviewRequest;
use Artwork\Modules\AiAssistant\Http\Requests\ShowAiWorkTimeRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StaffAiEventRequest;
use Artwork\Modules\AiAssistant\Http\Requests\StoreAiShiftAssignmentsBulkRequest;
use Artwork\Modules\AiAssistant\Http\Requests\UpdateAiTaskRequest;
use Artwork\Modules\AiAssistant\Services\AiAbsenceReadService;
use Artwork\Modules\AiAssistant\Services\AiBudgetSummaryService;
use Artwork\Modules\AiAssistant\Services\AiDayOverviewService;
use Artwork\Modules\AiAssistant\Services\AiEventStaffingService;
use Artwork\Modules\AiAssistant\Services\AiRoomConflictService;
use Artwork\Modules\AiAssistant\Services\AiShiftBulkAssignmentWriteService;
use Artwork\Modules\AiAssistant\Services\AiTaskService;
use Artwork\Modules\AiAssistant\Services\AiWorkTimeService;
use Artwork\Modules\AiAssistant\Support\AiErrors;
use Artwork\Modules\AiAssistant\Support\AiShiftPlanningAccess;
use Artwork\Modules\AiAssistant\Support\AiWriteIntent;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Policies\ProjectPolicy;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alltagsszenarien fuer artwork-ai (ai-scenarios-3 S1–S7).
 *
 * Unsichtbare Objekte → 404; fehlt bei einem sichtbaren Objekt ein Recht → 403 mit
 * `{"error": "<code>"}` (`AiErrors`).
 */
class AiScenarioApiController extends Controller
{
    public function __construct(
        private readonly AiDayOverviewService $dayOverview,
        private readonly AiBudgetSummaryService $budget,
        private readonly AiEventStaffingService $staffing,
        private readonly AiShiftBulkAssignmentWriteService $bulk,
        private readonly AiAbsenceReadService $absences,
        private readonly AiTaskService $tasks,
        private readonly AiRoomConflictService $conflicts,
        private readonly AiWorkTimeService $workTime,
        private readonly ProjectPolicy $projectPolicy,
    ) {
    }

    public function dayOverview(ShowAiDayOverviewRequest $request): AiDayOverviewData
    {
        return $this->dayOverview->overview($request->user(), $request->day());
    }

    public function budgetSummary(Request $request, int $project): AiBudgetSummaryData
    {
        /** @var User $user */
        $user = $request->user();
        $model = Project::query()->visibleFor($user)->whereKey($project)->first();

        abort_if($model === null, Response::HTTP_NOT_FOUND);
        abort_unless($this->projectPolicy->view($user, $model), Response::HTTP_NOT_FOUND);

        return $this->budget->summary($user, $model);
    }

    public function staffEvent(StaffAiEventRequest $request): AiEventStaffingData
    {
        return $this->staffing->suggest(
            $this->planner($request->user()),
            (int) $request->validated('event_id'),
            (int) $request->validated('craft_id'),
            $request->intOrNull('qualification_id'),
            $request->people(),
            $request->intOrNull('max_minutes_per_person_week'),
        );
    }

    public function bulkAssign(StoreAiShiftAssignmentsBulkRequest $request): AiWritePreviewData|AiWriteResultData
    {
        return $this->bulk->assign(
            $this->planner($request->user()),
            $request->rows(),
            AiWriteIntent::fromRequest($request),
        );
    }

    public function absences(IndexAiAbsencesRequest $request): AiPersonAbsencesListData
    {
        /** @var User $user */
        $user = $request->user();

        if (!AiShiftPlanningAccess::mayViewShiftPlan($user) && !AiShiftPlanningAccess::mayPlanShifts($user)) {
            AiErrors::abort(AiErrors::NO_SHIFT_PLAN_ACCESS);
        }

        return $this->absences->list(
            $request->from(),
            $request->to(),
            $request->craftId(),
            $request->search(),
            $request->limit(),
        );
    }

    public function myTasks(IndexAiMyTasksRequest $request): AiTaskListData
    {
        return $this->tasks->myTasks(
            $request->user(),
            $request->status(),
            $request->intOrNull('project_id'),
            $request->search(),
            $request->intOrNull('limit'),
        );
    }

    public function updateTask(UpdateAiTaskRequest $request, int $task): AiWritePreviewData|AiWriteResultData
    {
        $bodyTaskId = $request->validated('task_id');
        abort_if(
            $bodyTaskId !== null && (int) $bodyTaskId !== $task,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'task_id passt nicht zum Pfad.'
        );

        return $this->tasks->update(
            $request->user(),
            $task,
            $request->boolean('done'),
            AiWriteIntent::fromRequest($request, ['task_id' => $task]),
        );
    }

    public function roomConflicts(IndexAiRoomConflictsRequest $request): AiRoomConflictListData
    {
        return $this->conflicts->list(
            $request->user(),
            $request->from(),
            $request->to(),
            $request->roomId(),
            $request->limit(),
        );
    }

    public function workTime(ShowAiWorkTimeRequest $request, string $type, int $id): AiWorkTimeData
    {
        return $this->workTime->workTime($request->user(), $type, $id, $request->monthStart());
    }

    private function planner(?User $user): User
    {
        abort_unless($user !== null && AiShiftPlanningAccess::mayPlanShifts($user), Response::HTTP_FORBIDDEN);

        return $user;
    }
}

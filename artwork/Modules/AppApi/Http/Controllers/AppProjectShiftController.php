<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ShiftSettings;
use Artwork\Modules\AppApi\Http\Requests\AppShiftRequest;
use Artwork\Modules\AppApi\Services\AppSystemComponentService;
use Artwork\Modules\Craft\Services\CraftScopeService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftDeletionService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Artwork\Modules\Shift\Services\ShiftUpdateService;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AppProjectShiftController extends Controller
{
    public function __construct(
        private readonly AppSystemComponentService $systemComponentService,
        private readonly ShiftService $shiftService,
        private readonly ShiftsQualificationsService $shiftsQualificationsService,
        private readonly ShiftSettings $shiftSettings,
        private readonly DatabaseManager $db,
        private readonly CraftScopeService $craftScopeService,
        private readonly ShiftUpdateService $shiftUpdateService,
    ) {
    }

    /**
     * One shift with everything the editor needs — deeplinks do not have to
     * load the whole tab payload to edit a single shift.
     */
    public function show(Project $project, Shift $shift): JsonResponse
    {
        $this->authorizePlanning($project);

        return response()->json([
            'shift' => $this->systemComponentService->shiftPayload($shift),
            'overbooking_allowed' => $this->shiftSettings->allow_shift_overbooking,
            ...$this->systemComponentService->shiftEditorOptions(),
        ]);
    }

    public function store(AppShiftRequest $request, Project $project): JsonResponse
    {
        $this->authorizePlanning($project);
        // Gewerks-Scoping wie im Web (ShiftController::storeShiftWithoutEvent)
        $this->craftScopeService->assertCanPlan($request->user(), [$request->validated('craft_id')]);

        $shift = $this->db->transaction(function () use ($request, $project): Shift {
            // The web's creation path — it also derives end_date for shifts
            // running past midnight.
            $shift = $this->shiftService->createShiftWithoutEventAutomatic(
                craftId: (int) $request->validated('craft_id'),
                data: [
                    ...$request->validated(),
                    'project_id' => $project->id,
                    'description' => $request->validated('description'),
                    'room_id' => $request->validated('room_id'),
                ],
                day: (string) $request->validated('day'),
            );
            $shift->shift_uuid = Str::uuid();
            $this->shiftService->save($shift);

            $this->syncQualifications($shift, $request->validated('qualifications'));

            return $shift;
        });

        return response()->json(['shift' => $this->systemComponentService->shiftPayload($shift)], 201);
    }

    /**
     * The web's update path (ShiftUpdateService): craft change removes the
     * booked people, rules and conflicts are re-checked, committed shifts
     * notify their people and planners, project days resync and the shift
     * plan receives the live update.
     */
    public function update(AppShiftRequest $request, Project $project, Shift $shift): JsonResponse
    {
        $this->authorizePlanning($project);
        // Current AND target craft must be plannable (ShiftController::updateShift)
        $this->craftScopeService->assertCanPlan(
            $request->user(),
            [$shift->craft_id, $request->validated('craft_id')],
        );

        $start = Carbon::parse($request->validated('start'));
        $end = Carbon::parse($request->validated('end'));
        $day = Carbon::parse($request->validated('day'));

        $this->shiftUpdateService->update(
            $shift,
            [
                'start_date' => $day->format('Y-m-d'),
                'end_date' => $this->shiftService->endDateFor($day, $start, $end),
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'break_minutes' => $request->validated('break_minutes'),
                'description' => $request->validated('description'),
                'craft_id' => $request->validated('craft_id'),
                'room_id' => $request->validated('room_id'),
            ],
            $request->validated('qualifications'),
        );

        return response()->json(['shift' => $this->systemComponentService->shiftPayload($shift->refresh())]);
    }

    public function destroy(
        Request $request,
        Project $project,
        Shift $shift,
        ShiftDeletionService $shiftDeletionService,
    ): Response {
        $this->authorizePlanning($project);
        $this->craftScopeService->assertCanPlanShifts($request->user(), [$shift]);

        // The one delete path of the web: notifications, conflicts, rule re-check and live update.
        $shiftDeletionService->delete($shift);

        return response()->noContent();
    }

    /**
     * Applies the requested staffing requirements through the shared service —
     * like the web, a value below the people already booked is clamped up to
     * the booked count instead of leaving an unmarked overstaffing.
     *
     * @param array<int, array{shift_qualification_id: int, value: int}> $qualifications
     */
    private function syncQualifications(Shift $shift, array $qualifications): void
    {
        foreach ($qualifications as $qualification) {
            $this->shiftsQualificationsService->updateShiftsQualificationForShift($shift->id, $qualification);
        }
    }

    private function authorizePlanning(Project $project): void
    {
        $this->authorize('view', $project);
        $this->authorize('plan-shifts');
    }
}

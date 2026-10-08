<?php

namespace Artwork\Modules\AppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Settings\ShiftSettings;
use Artwork\Modules\AppApi\Http\Requests\AppShiftRequest;
use Artwork\Modules\AppApi\Services\AppSystemComponentService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Services\ShiftDeletionService;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\Shift\Services\ShiftsQualificationsService;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
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

    public function update(AppShiftRequest $request, Project $project, Shift $shift): JsonResponse
    {
        $this->authorizePlanning($project);

        $this->db->transaction(function () use ($request, $shift): void {
            $start = Carbon::parse($request->validated('start'));
            $end = Carbon::parse($request->validated('end'));
            $day = Carbon::parse($request->validated('day'));

            $shift->update([
                'start_date' => $day->format('Y-m-d'),
                'end_date' => $this->shiftService->endDateFor($day, $start, $end),
                'start' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'break_minutes' => $request->validated('break_minutes'),
                'description' => $request->validated('description'),
                'craft_id' => $request->validated('craft_id'),
                'room_id' => $request->validated('room_id'),
            ]);

            $this->syncQualifications($shift, $request->validated('qualifications'));
        });

        return response()->json(['shift' => $this->systemComponentService->shiftPayload($shift)]);
    }

    public function destroy(Project $project, Shift $shift, ShiftDeletionService $shiftDeletionService): Response
    {
        $this->authorizePlanning($project);

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

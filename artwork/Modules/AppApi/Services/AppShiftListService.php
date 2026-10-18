<?php

namespace Artwork\Modules\AppApi\Services;

use Artwork\Modules\Shift\Services\ShiftListViewService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;

class AppShiftListService
{
    public function __construct(
        private readonly ShiftListViewService $shiftListViewService,
    ) {
    }

    /**
     * The Dienstplan-Listenansicht (day → room → shifts) in the app contract
     * shape. Wraps the web list view's aggregation with the user's persisted web
     * settings and filters — app mirrors exactly what the user sees on the
     * web — and slims the payload to the fields the app renders. The app
     * validates this shape on the device, so keys are load-bearing.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDays(User $user, Carbon $startDate, Carbon $endDate): array
    {
        // Same settings and filter rows the web list view uses; only the date
        // range comes from the request.
        $groupedShifts = $this->shiftListViewService->getGroupedShifts(
            $startDate,
            $endDate,
            $this->shiftListViewService->settingsFor($user),
            $this->shiftListViewService->filterFor($user),
        );

        $days = [];
        foreach ($groupedShifts as $group) {
            $rooms = [];
            foreach ($group['rooms'] as $room) {
                // Appointment-only room buckets (settings.show_appointments) carry
                // no shifts — the app calendar screen covers events instead.
                if ($room['shifts'] === []) {
                    continue;
                }

                $rooms[] = [
                    // Core groups roomless shifts under a sentinel id 0 — the
                    // wire promises a real id or null.
                    'id' => ($room['room']['id'] ?? 0) === 0 ? null : $room['room']['id'],
                    'name' => $room['room']['name'],
                    'shifts' => array_map(
                        fn (array $shift): array => $this->mapShift($shift),
                        $room['shifts'],
                    ),
                ];
            }

            if ($rooms === []) {
                continue;
            }

            $days[] = [
                'date' => $group['day'],
                'holidays' => array_map(
                    static fn (array $holiday): array => [
                        'name' => $holiday['name'],
                        'color' => $holiday['color'],
                    ],
                    $group['holidays'],
                ),
                'rooms' => $rooms,
                'open_count' => array_sum(array_map(
                    static fn (array $room): int => array_sum(array_column($room['shifts'], 'open_count')),
                    $rooms,
                )),
            ];
        }

        return $days;
    }

    /**
     * Slims a ShiftListViewSerializer shift down to what the app renders.
     * required_count/assigned_count reproduce the web list view's staffing
     * counter (workers vs. sum of the qualification requirements).
     *
     * @param array<string, mixed> $shift
     * @return array<string, mixed>
     */
    private function mapShift(array $shift): array
    {
        $requiredCount = (int) array_sum(array_column($shift['shifts_qualifications'], 'value'));
        $assignedCount = count($shift['workers']);

        return [
            'id' => $shift['id'],
            'start' => $shift['start'],
            'end' => $shift['end'],
            'break_minutes' => (int) $shift['break_minutes'],
            'description' => $shift['description'],
            'is_committed' => (bool) $shift['is_committed'],
            'in_workflow' => (bool) $shift['in_workflow'],
            'craft' => $shift['craft'],
            'project' => $shift['project'] ?? $shift['event']['project'] ?? null,
            'required_count' => $requiredCount,
            'assigned_count' => $assignedCount,
            'open_count' => max($requiredCount - $assignedCount, 0),
            'workers' => array_map(
                static fn (array $worker): array => [
                    'id' => $worker['id'],
                    'type' => $worker['type'],
                    'name' => $worker['name'],
                    'is_unavailable' => (bool) $worker['is_unavailable'],
                ],
                $shift['workers'],
            ),
        ];
    }
}

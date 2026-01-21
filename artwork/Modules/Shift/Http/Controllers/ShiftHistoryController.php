<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class ShiftHistoryController
{
    public function index(Request $request): JsonResponse
    {
        $craftId = (int) $request->query('craftId');
        if ($craftId <= 0) {
            return response()->json([
                'shifts' => [],
                'logs'   => [
                    'data' => [],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 0],
                ],
            ], 422);
        }

        // Zeitraum (Default: aktueller Monat)
        $startParam = $request->query('start_date');
        $endParam   = $request->query('end_date');

        $now = Carbon::now(config('app.timezone', 'Europe/Berlin'));

        $startDate = $startParam
            ? Carbon::parse($startParam, config('app.timezone', 'Europe/Berlin'))->startOfDay()
            : $now->copy()->startOfMonth()->startOfDay();

        $endDate = $endParam
            ? Carbon::parse($endParam, config('app.timezone', 'Europe/Berlin'))->endOfDay()
            : $now->copy()->endOfMonth()->endOfDay();

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(200, $perPage));

        // Shifts im Zeitraum (Overlaps!)
        $shifts = Shift::query()
            ->select([
                'id',
                'craft_id',
                'start_date',
                'end_date',
                'start',
                'end',
                'description',
                'room_id',
                'project_id',
                'is_committed',
                'in_workflow',
            ])
            ->with([
                'room:id,name',
                'project:id,name',
                'craft:id,name,abbreviation',
            ])
            ->where('craft_id', $craftId)
            ->startAndEndDateOverlap($startDate->toDateString(), $endDate->toDateString())
            ->orderBy('start_date')
            ->orderBy('start')
            ->get();

        $shiftIds = $shifts->pluck('id')->all();

        if (empty($shiftIds)) {
            return response()->json([
                'shifts' => $shifts,
                'logs'   => [
                    'data' => [],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => $perPage, 'total' => 0],
                ],
                'range'  => [
                    'start_date' => $startDate->toDateString(),
                    'end_date'   => $endDate->toDateString(),
                ],
            ]);
        }

        // Activity Logs für diese Shifts (Spatie activity_log)
        $paginator = Activity::query()
            ->where('log_name', 'shift')
            ->where('subject_type', Shift::class)
            ->whereIn('subject_id', $shiftIds)
            ->with([
                // Causer: bei dir ist das offenbar ein User-Objekt mit first/last/full_name/type/profile_photo_url
                'causer',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'shifts' => $shifts,
            'logs'   => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ],
            'range'  => [
                'start_date' => $startDate->toDateString(),
                'end_date'   => $endDate->toDateString(),
            ],
        ]);
    }
}

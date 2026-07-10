<?php

namespace Artwork\Modules\Inventory\Http\Controllers;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class MaterialIssueLogController
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $projectId = (int) $request->query('project_id', 0);

        $startParam = $request->query('start_date');
        $endParam = $request->query('end_date');

        $now = Carbon::now(config('app.timezone', 'Europe/Berlin'));

        $startDate = $startParam
            ? Carbon::parse($startParam, config('app.timezone', 'Europe/Berlin'))->startOfDay()
            : $now->copy()->startOfMonth()->startOfDay();

        $endDate = $endParam
            ? Carbon::parse($endParam, config('app.timezone', 'Europe/Berlin'))->endOfDay()
            : $now->copy()->endOfMonth()->endOfDay();

        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min(200, $perPage));

        // Internal issues in date range
        $internalQuery = InternalIssue::query()
            ->when($projectId > 0, fn($q) => $q->where('project_id', $projectId))
            ->overlapping($startDate->toDateString(), $endDate->toDateString());
        $internalIds = $internalQuery->pluck('id')->all();

        // External issues in date range. External issues carry no project —
        // exclude them entirely when a project filter is active.
        $externalIds = $projectId > 0
            ? []
            : ExternalIssue::query()
                ->overlapping($startDate->toDateString(), $endDate->toDateString())
                ->pluck('id')
                ->all();

        $paginator = Activity::query()
            ->where('log_name', 'material_issue')
            ->where(function ($q) use ($internalIds, $externalIds, $projectId, $startDate, $endDate) {
                $q->when(!empty($internalIds), function ($qq) use ($internalIds) {
                    $qq->orWhere(function ($qqq) use ($internalIds) {
                        $qqq->where('subject_type', InternalIssue::class)
                            ->whereIn('subject_id', $internalIds);
                    });
                })
                ->when(!empty($externalIds), function ($qq) use ($externalIds) {
                    $qq->orWhere(function ($qqq) use ($externalIds) {
                        $qqq->where('subject_type', ExternalIssue::class)
                            ->whereIn('subject_id', $externalIds);
                    });
                })
                // Issues are hard-deleted — their history (incl. the delete
                // entry) can only be matched via the activity date itself.
                ->orWhere(function ($qq) use ($projectId, $startDate, $endDate) {
                    $qq->where('subject_type', InternalIssue::class)
                        ->whereNotIn('subject_id', InternalIssue::query()->select('id'))
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->when($projectId > 0, fn($x) => $x->where('properties->project_id', $projectId));
                })
                ->when($projectId <= 0, function ($qq) use ($startDate, $endDate) {
                    $qq->orWhere(function ($qqq) use ($startDate, $endDate) {
                        $qqq->where('subject_type', ExternalIssue::class)
                            ->whereNotIn('subject_id', ExternalIssue::query()->select('id'))
                            ->whereBetween('created_at', [$startDate, $endDate]);
                    });
                });
            })
            ->with('causer')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'logs' => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            'range' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ]);
    }
}

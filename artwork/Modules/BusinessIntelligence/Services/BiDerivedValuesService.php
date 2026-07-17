<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Repositories\BiEventTypeTagRepository;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SageAssignedData;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiDerivedValuesService
{
    public function __construct(
        private readonly BiEventTypeTagRepository $biEventTypeTagRepository
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getDerivedValues(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $taskCounts = $this->getTaskCounts($project);

        return [
            'contract_count' => $project->contracts()->count(),
            'event_count' => $this->scopeEventsByRange($project->events(), $from, $to)->count(),
            'booking_count' => $this->getBookingCount($project),
            'task_total' => $taskCounts['total'],
            'task_open' => $taskCounts['open'],
            'task_done' => $taskCounts['done'],
            'document_count' => $project->project_files()->count(),
            'department_count' => $project->departments()->count(),
            'user_count' => $project->users()->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTagBasedCounts(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $tags = $this->biEventTypeTagRepository->getAllWithEventTypes();
        $counts = [];

        foreach ($tags as $tag) {
            $counts[] = [
                'tag_id' => $tag->id,
                'tag_name' => $tag->name,
                'tag_name_de' => $tag->name_de,
                'color' => $tag->color,
                'count' => $this->countDistinctDaysForTag($project, $tag, $from, $to),
            ];
        }

        return $counts;
    }

    private function countDistinctDaysForTag(
        Project $project,
        BiEventTypeTag $tag,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): int {
        $eventTypeIds = $tag->eventTypes->pluck('id')->toArray();

        if (empty($eventTypeIds)) {
            return 0;
        }

        $events = $this->scopeEventsByRange($project->events(), $from, $to)
            ->whereIn('event_type_id', $eventTypeIds)
            ->get(['start_time', 'end_time']);

        $uniqueDates = collect();

        foreach ($events as $event) {
            // start_time/end_time sind nullable — ohne beide Daten ist keine Tageszählung möglich
            if (!$event->start_time || !$event->end_time) {
                continue;
            }

            $start = $event->start_time->copy()->startOfDay();
            $end = $event->end_time->copy()->startOfDay();

            if ($from && $start->lt($from->copy()->startOfDay())) {
                $start = $from->copy()->startOfDay();
            }

            if ($to && $end->gt($to->copy()->startOfDay())) {
                $end = $to->copy()->startOfDay();
            }

            if ($end->lt($start)) {
                continue;
            }

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $uniqueDates->push($date->format('Y-m-d'));
            }
        }

        return $uniqueDates->unique()->count();
    }

    /**
     * Limits an events relation to those overlapping the given date range.
     */
    private function scopeEventsByRange(HasMany $query, ?Carbon $from, ?Carbon $to): HasMany
    {
        if ($from) {
            $query->where('end_time', '>=', $from->copy()->startOfDay());
        }

        if ($to) {
            $query->where('start_time', '<=', $to->copy()->endOfDay());
        }

        return $query;
    }

    public function getBookingCount(Project $project): int
    {
        // Property-Zugriff nutzt die eager-geladene Relation (table()->first() feuert immer eine frische Query)
        $table = $project->table;

        if (!$table) {
            return 0;
        }

        $cellIds = ColumnCell::whereIn(
            'sub_position_row_id',
            SubPositionRow::whereIn(
                'sub_position_id',
                SubPosition::whereIn(
                    'main_position_id',
                    MainPosition::where('table_id', $table->id)->select('id')
                )->select('id')
            )->select('id')
        )->select('id');

        return SageAssignedData::whereIn('column_cell_id', $cellIds)->count();
    }

    /**
     * @return array{total: int, open: int, done: int}
     */
    private function getTaskCounts(Project $project): array
    {
        $checklists = $project->checklists()->with('tasks')->get();

        $total = 0;
        $done = 0;

        foreach ($checklists as $checklist) {
            foreach ($checklist->tasks as $task) {
                $total++;
                if ($task->done) {
                    $done++;
                }
            }
        }

        return [
            'total' => $total,
            'open' => $total - $done,
            'done' => $done,
        ];
    }
}

<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Repositories\BiEventTypeTagRepository;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class BiDerivedValuesService
{
    public function __construct(
        private readonly BiEventTypeTagRepository $biEventTypeTagRepository
    ) {
    }

    public function getDerivedValues(Project $project): array
    {
        return [
            'contract_count' => $project->contracts()->count(),
            'event_count' => $project->events()->count(),
            'task_total' => $this->getTaskCounts($project)['total'],
            'task_open' => $this->getTaskCounts($project)['open'],
            'task_done' => $this->getTaskCounts($project)['done'],
            'document_count' => $project->project_files()->count(),
            'department_count' => $project->departments()->count(),
            'user_count' => $project->users()->count(),
        ];
    }

    public function getTagBasedCounts(Project $project): array
    {
        $tags = $this->biEventTypeTagRepository->getAllWithEventTypes();
        $counts = [];

        foreach ($tags as $tag) {
            $counts[] = [
                'tag_id' => $tag->id,
                'tag_name' => $tag->name,
                'tag_name_de' => $tag->name_de,
                'color' => $tag->color,
                'count' => $this->countDistinctDaysForTag($project, $tag),
            ];
        }

        return $counts;
    }

    private function countDistinctDaysForTag(Project $project, BiEventTypeTag $tag): int
    {
        $eventTypeIds = $tag->eventTypes->pluck('id')->toArray();

        if (empty($eventTypeIds)) {
            return 0;
        }

        $events = $project->events()
            ->whereIn('event_type_id', $eventTypeIds)
            ->get(['start_time', 'end_time']);

        $uniqueDates = collect();

        foreach ($events as $event) {
            $start = $event->start_time->startOfDay();
            $end = $event->end_time->startOfDay();

            $period = CarbonPeriod::create($start, $end);

            foreach ($period as $date) {
                $uniqueDates->push($date->format('Y-m-d'));
            }
        }

        return $uniqueDates->unique()->count();
    }

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

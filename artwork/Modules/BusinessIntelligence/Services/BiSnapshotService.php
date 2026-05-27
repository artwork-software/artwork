<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Models\BiSnapshot;
use Artwork\Modules\BusinessIntelligence\Repositories\BiSnapshotRepository;
use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class BiSnapshotService
{
    public function __construct(
        private readonly BiSnapshotRepository $biSnapshotRepository,
        private readonly BiProjectDataService $biProjectDataService,
        private readonly BiDerivedValuesService $biDerivedValuesService
    ) {
    }

    public function getByProjectId(int $projectId): Collection
    {
        return $this->biSnapshotRepository->getByProjectId($projectId);
    }

    public function create(Project $project, string $name, string $snapshotDate, int $userId): BiSnapshot
    {
        $biData = $this->biProjectDataService->getOrCreateForProject($project->id);
        $eventData = $this->biProjectDataService->getEventData($project->id);
        $derivedValues = $this->biDerivedValuesService->getDerivedValues($project);
        $tagCounts = $this->biDerivedValuesService->getTagBasedCounts($project);
        $roomCapacities = $this->biProjectDataService->getRoomCapacities($project->id);
        $timeEfforts = $project->biTimeEfforts()->with('user')->get();

        $snapshotData = [
            'bi_data' => $biData->toArray(),
            'event_data' => $eventData->toArray(),
            'derived_values' => $derivedValues,
            'tag_counts' => $tagCounts,
            'room_capacities' => $roomCapacities->toArray(),
            'time_efforts' => $timeEfforts->toArray(),
        ];

        $snapshot = $this->biSnapshotRepository->getNewModelInstance();
        $snapshot->fill([
            'project_id' => $project->id,
            'name' => $name,
            'snapshot_date' => $snapshotDate,
            'data' => $snapshotData,
            'created_by' => $userId,
        ]);
        $this->biSnapshotRepository->save($snapshot);

        return $snapshot;
    }

    public function delete(BiSnapshot $snapshot): bool
    {
        return $this->biSnapshotRepository->delete($snapshot);
    }
}

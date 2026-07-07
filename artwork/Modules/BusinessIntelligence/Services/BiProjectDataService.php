<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\BusinessIntelligence\Repositories\BiEventDataRepository;
use Artwork\Modules\BusinessIntelligence\Repositories\BiProjectDataRepository;
use Artwork\Modules\BusinessIntelligence\Repositories\BiProjectRoomCapacityRepository;
use Artwork\Modules\BusinessIntelligence\Models\BiEventData;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectRoomCapacity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class BiProjectDataService
{
    public function __construct(
        private readonly BiProjectDataRepository $biProjectDataRepository,
        private readonly BiEventDataRepository $biEventDataRepository,
        private readonly BiProjectRoomCapacityRepository $biProjectRoomCapacityRepository
    ) {
    }

    public function getOrCreateForProject(int $projectId): BiProjectData
    {
        return $this->biProjectDataRepository->firstOrCreateForProject($projectId);
    }

    public function updateData(int $projectId, array $data): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId);
        $this->biProjectDataRepository->update($biData, $data);
        $this->bumpDashboardCacheVersion();

        return $biData->fresh();
    }

    /**
     * Invalidiert die BI-Dashboard-Caches (der Versions-Stand ist Teil des Cache-Keys).
     */
    private function bumpDashboardCacheVersion(): void
    {
        Cache::increment('bi_dashboard_version');
    }

    public function switchVisitorMode(int $projectId, BiVisitorModeEnum $mode): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId);

        if ($biData->visitor_mode === $mode) {
            return $biData;
        }

        if ($mode === BiVisitorModeEnum::TOTAL) {
            $this->biEventDataRepository->getByProjectId($projectId)
                ->each(function (BiEventData $eventData) {
                    $eventData->update(['visitors' => null]);
                });
        } else {
            $biData->update(['visitors_total' => null]);
        }

        $this->biProjectDataRepository->update($biData, ['visitor_mode' => $mode->value]);
        $this->bumpDashboardCacheVersion();

        return $biData->fresh();
    }

    public function switchSoldTicketsMode(int $projectId, BiVisitorModeEnum $mode): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId);

        if ($biData->sold_tickets_mode === $mode) {
            return $biData;
        }

        if ($mode === BiVisitorModeEnum::TOTAL) {
            $this->biEventDataRepository->getByProjectId($projectId)
                ->each(function (BiEventData $eventData) {
                    $eventData->update(['sold_tickets' => null]);
                });
        } else {
            $biData->update(['sold_tickets_total' => null]);
        }

        $this->biProjectDataRepository->update($biData, ['sold_tickets_mode' => $mode->value]);
        $this->bumpDashboardCacheVersion();

        return $biData->fresh();
    }

    public function switchRevenueMode(int $projectId, BiVisitorModeEnum $mode): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId);

        if ($biData->revenue_mode === $mode) {
            return $biData;
        }

        if ($mode === BiVisitorModeEnum::TOTAL) {
            $this->biEventDataRepository->getByProjectId($projectId)
                ->each(function (BiEventData $eventData) {
                    $eventData->update(['revenue' => null]);
                });
        } else {
            $biData->update(['revenue_total' => null]);
        }

        $this->biProjectDataRepository->update($biData, ['revenue_mode' => $mode->value]);
        $this->bumpDashboardCacheVersion();

        return $biData->fresh();
    }

    public function upsertEventData(int $projectId, int $eventId, array $data): BiEventData
    {
        $eventData = $this->biEventDataRepository->upsert($projectId, $eventId, $data);
        $this->bumpDashboardCacheVersion();

        return $eventData;
    }

    public function getEventData(int $projectId): Collection
    {
        return $this->biEventDataRepository->getByProjectId($projectId);
    }

    public function getRoomCapacities(int $projectId): Collection
    {
        return $this->biProjectRoomCapacityRepository->getByProjectId($projectId);
    }

    public function updateRoomCapacity(int $projectId, int $roomId, ?int $capacityOverride): BiProjectRoomCapacity
    {
        $capacity = $this->biProjectRoomCapacityRepository->upsert($projectId, $roomId, $capacityOverride);
        $this->bumpDashboardCacheVersion();

        return $capacity;
    }
}

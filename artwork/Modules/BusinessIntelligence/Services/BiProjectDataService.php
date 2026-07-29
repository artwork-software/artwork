<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategoryValue;
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

    public function getOrCreateForProject(int $projectId, string $scope = 'actual'): BiProjectData
    {
        return $this->biProjectDataRepository->firstOrCreateForProject($projectId, $scope);
    }

    public function updateData(int $projectId, array $data, string $scope = 'actual'): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId, $scope);
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

    public function switchVisitorMode(int $projectId, BiVisitorModeEnum $mode, string $scope = 'actual'): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId, $scope);

        if ($biData->visitor_mode === $mode) {
            return $biData;
        }

        if ($mode === BiVisitorModeEnum::TOTAL) {
            $this->biEventDataRepository->getByProjectId($projectId, $scope)
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

    public function switchSoldTicketsMode(int $projectId, BiVisitorModeEnum $mode, string $scope = 'actual'): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId, $scope);

        if ($biData->sold_tickets_mode === $mode) {
            return $biData;
        }

        if ($mode === BiVisitorModeEnum::TOTAL) {
            $this->biEventDataRepository->getByProjectId($projectId, $scope)
                ->each(function (BiEventData $eventData) {
                    $eventData->update(['sold_tickets' => null]);
                });
            // Kategorien folgen dem Ticket-Modus: Pro-Termin-Werte verwerfen
            BiAudienceCategoryValue::where('project_id', $projectId)
                ->where('scope', $scope)
                ->whereNotNull('event_id')
                ->delete();
        } else {
            $biData->update(['sold_tickets_total' => null]);
            BiAudienceCategoryValue::where('project_id', $projectId)
                ->where('scope', $scope)
                ->whereNull('event_id')
                ->delete();
        }

        $this->biProjectDataRepository->update($biData, ['sold_tickets_mode' => $mode->value]);
        $this->bumpDashboardCacheVersion();

        return $biData->fresh();
    }

    public function switchRevenueMode(int $projectId, BiVisitorModeEnum $mode, string $scope = 'actual'): BiProjectData
    {
        $biData = $this->getOrCreateForProject($projectId, $scope);

        if ($biData->revenue_mode === $mode) {
            return $biData;
        }

        if ($mode === BiVisitorModeEnum::TOTAL) {
            $this->biEventDataRepository->getByProjectId($projectId, $scope)
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

    public function upsertEventData(int $projectId, int $eventId, array $data, string $scope = 'actual'): BiEventData
    {
        $eventData = $this->biEventDataRepository->upsert($projectId, $eventId, $data, $scope);
        $this->bumpDashboardCacheVersion();

        return $eventData;
    }

    public function getEventData(int $projectId, string $scope = 'actual'): Collection
    {
        return $this->biEventDataRepository->getByProjectId($projectId, $scope);
    }

    public function getCategoryValues(int $projectId, string $scope = 'actual'): Collection
    {
        return BiAudienceCategoryValue::query()
            ->where('project_id', $projectId)
            ->where('scope', $scope)
            ->get();
    }

    /**
     * Bulk-Upsert der Kategorie-Werte. quantity null löscht den Eintrag
     * (= "nichts erfasst", damit Summen nicht durch 0-Zeilen verfälscht werden).
     *
     * @param array<int, array{bi_audience_category_id: int, event_id: ?int, quantity: ?int}> $entries
     */
    public function upsertCategoryValues(int $projectId, array $entries, string $scope = 'actual'): Collection
    {
        foreach ($entries as $entry) {
            $keys = [
                'project_id' => $projectId,
                'bi_audience_category_id' => $entry['bi_audience_category_id'],
                'event_id' => $entry['event_id'] ?? null,
                'scope' => $scope,
            ];

            if (($entry['quantity'] ?? null) === null) {
                BiAudienceCategoryValue::where($keys)->delete();
                continue;
            }

            BiAudienceCategoryValue::updateOrCreate($keys, ['quantity' => $entry['quantity']]);
        }

        $this->bumpDashboardCacheVersion();

        return $this->getCategoryValues($projectId, $scope);
    }

    /**
     * Plan-Schnellstart: legt den Plan-Datensatz an und befüllt ihn je nach Modus.
     * - empty: leere Plan-Zeile
     * - copy_actual_structure: Erfassungsmodi der Ist-Zeile übernehmen (Werte leer)
     * - copy_project: Ist-Gesamtwerte eines Quellprojekts als Plan-Gesamtwerte
     *   (typisch: Vorgänger-Produktion als Vorlage bei Wiederaufnahmen)
     */
    public function initializePlan(
        int $projectId,
        string $mode,
        ?\Artwork\Modules\Project\Models\Project $sourceProject = null
    ): BiProjectData {
        $plan = $this->getOrCreateForProject($projectId, 'plan');

        if ($mode === 'copy_actual_structure') {
            $actual = $this->biProjectDataRepository->findByProjectId($projectId);

            if ($actual) {
                $this->biProjectDataRepository->update($plan, [
                    'visitor_mode' => $actual->visitor_mode?->value ?? 'total',
                    'sold_tickets_mode' => $actual->sold_tickets_mode?->value ?? 'total',
                    'revenue_mode' => $actual->revenue_mode?->value ?? 'total',
                ]);
            }
        }

        if ($mode === 'copy_project' && $sourceProject) {
            $metrics = app(BiProjectMetricsService::class);

            $this->biProjectDataRepository->update($plan, [
                'visitor_mode' => 'total',
                'sold_tickets_mode' => 'total',
                'revenue_mode' => 'total',
                'visitors_total' => $metrics->visitors($sourceProject),
                'sold_tickets_total' => $metrics->soldTickets($sourceProject),
                'revenue_total' => $metrics->revenue($sourceProject),
            ]);
        }

        $this->bumpDashboardCacheVersion();

        return $plan->fresh();
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

<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiEventBriefData;
use Artwork\Modules\AiAssistant\DTOs\AiProjectListData;
use Artwork\Modules\AiAssistant\DTOs\AiPeriodData;
use Artwork\Modules\AiAssistant\DTOs\AiProjectDetailData;
use Artwork\Modules\AiAssistant\DTOs\AiProjectSummaryData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Lesezugriffe auf Projekte fuer artwork-ai (core-api-context-projects; Konzept B1 §3–4).
 *
 * Jede Abfrage laeuft ueber Project::visibleFor($user) — die eine Sichtbarkeitsregel aus
 * core-visibility-scopes. Der Dienst rechnet nichts aus, was die Oberflaeche nicht auch zeigt.
 */
class AiProjectReadService
{
    /** Anzahl der Termine, die das Projektdetail vorausschauend nennt (Brief B1a). */
    private const NEXT_EVENTS = 5;

    /**
     * @param array{q?: string|null, state?: int|null, from?: string|null, to?: string|null, limit?: int|null} $filters
     */
    public function search(User $user, array $filters): AiProjectListData
    {
        $limit = $this->limit($filters['limit'] ?? null);

        $query = Project::query()
            ->visibleFor($user)
            ->with('status')
            ->when(
                ($filters['q'] ?? null) !== null && $filters['q'] !== '',
                // Dieselbe LIKE-Suche wie die Projektuebersicht (ProjectService::getProjects());
                // Wildcards im Suchbegriff werden maskiert, sonst matcht "%" alles.
                fn (Builder $builder) => $builder->where(
                    'name',
                    'like',
                    '%' . $this->escapeLike((string) $filters['q']) . '%'
                )
            )
            ->when(
                ($filters['state'] ?? null) !== null,
                fn (Builder $builder) => $builder->where('state', $filters['state'])
            );

        $periodFilter = $this->periodFilter($filters);

        if ($periodFilter !== null) {
            [$from, $to] = $periodFilter;
            $query->whereHas(
                'events',
                fn (Builder $events) => $events
                    ->where('start_time', '<=', $to)
                    ->where('end_time', '>=', $from)
            );
        }

        $total = (clone $query)->count();
        $projects = $query->orderBy('name')->limit($limit)->get();
        $periods = $this->periodsFor($projects->pluck('id')->all());
        $counts = $this->eventAndRoomCounts($projects->pluck('id')->all());

        return new AiProjectListData(
            data: $projects->map(fn (Project $project): AiProjectSummaryData => new AiProjectSummaryData(
                id: $project->id,
                name: AiText::truncate($project->name),
                state: AiText::truncate($project->status?->name),
                period: $periods[$project->id] ?? new AiPeriodData(null, null),
                room_count: $counts[$project->id]['rooms'] ?? 0,
                event_count: $counts[$project->id]['events'] ?? 0,
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    public function detail(Project $project): AiProjectDetailData
    {
        $project->loadMissing('status');
        $counts = $this->eventAndRoomCounts([$project->id]);

        $nextEvents = Event::query()
            ->where('project_id', $project->id)
            ->where('end_time', '>=', CarbonImmutable::now())
            ->with('room')
            ->orderBy('start_time')
            ->limit(self::NEXT_EVENTS)
            ->get();

        return new AiProjectDetailData(
            id: $project->id,
            name: AiText::truncate($project->name),
            state: AiText::truncate($project->status?->name),
            shift_description: AiText::truncate($project->shift_description),
            period: $this->periodsFor([$project->id])[$project->id] ?? new AiPeriodData(null, null),
            team_size: $project->users()->count(),
            event_count: $counts[$project->id]['events'] ?? 0,
            room_count: $counts[$project->id]['rooms'] ?? 0,
            next_events: $nextEvents->map(AiEventBriefData::fromModel(...))->all(),
        );
    }

    /**
     * Projektzeitraum wie die Projektuebersicht (ProjectController::getProjectPeriods()):
     * Termine mit `relevant_for_project_period` bestimmen den Zeitraum, sonst alle Termine.
     *
     * @param int[] $projectIds
     * @return array<int, AiPeriodData>
     */
    private function periodsFor(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $relevant = $this->periodQuery($projectIds, true)->keyBy('project_id');
        $fallback = $this->periodQuery($projectIds, false)->keyBy('project_id');

        $periods = [];

        foreach ($projectIds as $projectId) {
            $row = $relevant->get($projectId) ?? $fallback->get($projectId);

            $periods[$projectId] = $row && $row->first_start && $row->last_end
                ? AiPeriodData::fromDates(
                    CarbonImmutable::parse($row->first_start),
                    CarbonImmutable::parse($row->last_end)
                )
                : new AiPeriodData(null, null);
        }

        return $periods;
    }

    /**
     * @param int[] $projectIds
     * @return Collection<int, object>
     */
    private function periodQuery(array $projectIds, bool $onlyRelevantTypes): Collection
    {
        return Event::query()
            ->when(
                $onlyRelevantTypes,
                fn (Builder $builder) => $builder
                    ->join('event_types', 'events.event_type_id', '=', 'event_types.id')
                    ->where('event_types.relevant_for_project_period', true)
            )
            ->whereIn('events.project_id', $projectIds)
            ->groupBy('events.project_id')
            ->selectRaw('events.project_id, MIN(events.start_time) as first_start, MAX(events.end_time) as last_end')
            ->get();
    }

    /**
     * @param int[] $projectIds
     * @return array<int, array{events: int, rooms: int}>
     */
    private function eventAndRoomCounts(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        return Event::query()
            ->whereIn('project_id', $projectIds)
            ->groupBy('project_id')
            ->selectRaw('project_id, COUNT(*) as event_count, COUNT(DISTINCT room_id) as room_count')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                (int) $row->project_id => ['events' => (int) $row->event_count, 'rooms' => (int) $row->room_count],
            ])
            ->all();
    }

    /**
     * @param array{from?: string|null, to?: string|null} $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function periodFilter(array $filters): ?array
    {
        if (($filters['from'] ?? null) === null || ($filters['to'] ?? null) === null) {
            return null;
        }

        return [
            CarbonImmutable::parse((string) $filters['from'])->startOfDay(),
            CarbonImmutable::parse((string) $filters['to'])->endOfDay(),
        ];
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

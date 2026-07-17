<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectRoleMatrixExportService
{
    /**
     * Matrix Produktionen × Personen: aufgenommen werden nur Personen, die eine feste
     * Projektrolle (default_project_role_user) besitzen UND im Zeitraum in mindestens
     * einem Projekt mit einer Projektrolle im Team stehen. Ein Projekt zählt zum
     * Zeitraum, wenn sich mindestens ein Termin damit überschneidet.
     *
     * @return array{
     *     projects: Collection<int, array{id: int, name: string, period_label: string}>,
     *     rows: Collection<int, array{name: string, roles: array<int, string>}>
     * }
     */
    public function buildMatrix(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $rangeStart = $rangeStart->copy()->startOfDay();
        $rangeEnd = $rangeEnd->copy()->endOfDay();

        $projects = Project::query()
            ->where('is_group', false)
            ->whereHas(
                'events',
                fn ($query) => $query
                    ->where('start_time', '<=', $rangeEnd)
                    ->where('end_time', '>=', $rangeStart)
            )
            ->with('users')
            ->withMin('events', 'start_time')
            ->withMax('events', 'end_time')
            ->get()
            ->sortBy([['events_min_start_time', 'asc'], ['name', 'asc']])
            ->values();

        $roleNames = ProjectRole::query()->orderBy('name')->pluck('name', 'id');
        $userIdsWithDefaultRole = DB::table('default_project_role_user')
            ->distinct()
            ->pluck('user_id')
            ->flip();

        $rows = [];
        foreach ($projects as $project) {
            foreach ($project->users as $user) {
                $roleIds = array_map('intval', $user->pivot->roles ?? []);
                if ($roleIds === [] || !isset($userIdsWithDefaultRole[$user->id])) {
                    continue;
                }

                $labels = $roleNames->only($roleIds)->values()->implode(', ');
                if ($labels === '') {
                    continue;
                }

                $rows[$user->id] ??= [
                    'last_name' => (string) $user->last_name,
                    'first_name' => (string) $user->first_name,
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'roles' => [],
                ];
                $rows[$user->id]['roles'][$project->id] = $labels;
            }
        }

        return [
            'projects' => $projects->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => (string) $project->name,
                'period_label' => $this->periodLabel($project),
            ]),
            'rows' => collect($rows)
                ->sortBy([['last_name', 'asc'], ['first_name', 'asc']])
                ->map(fn (array $row) => ['name' => $row['name'], 'roles' => $row['roles']])
                ->values(),
        ];
    }

    private function periodLabel(Project $project): string
    {
        $firstStart = $project->getAttribute('events_min_start_time');
        $lastEnd = $project->getAttribute('events_max_end_time');
        if ($firstStart === null || $lastEnd === null) {
            return '';
        }

        return Carbon::parse($firstStart)->format('d.m.Y') . ' – ' . Carbon::parse($lastEnd)->format('d.m.Y');
    }
}

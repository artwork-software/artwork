<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Exports\BiProjectExport;
use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BiExportService
{
    public function __construct(
        private readonly BiProjectDataService $biProjectDataService,
        private readonly BiDerivedValuesService $biDerivedValuesService
    ) {
    }

    public function cacheExportConfiguration(array $config): string
    {
        $token = Str::uuid()->toString();
        Cache::put('bi_export_' . $token, $config, now()->addMinutes(30));

        return $token;
    }

    public function download(string $cacheToken): BinaryFileResponse
    {
        $config = Cache::get('bi_export_' . $cacheToken);

        if (!$config) {
            abort(404, 'Export configuration expired or not found.');
        }

        $projects = Project::whereIn('id', $config['project_ids'])
            ->with([
                'biData',
                'biEventData',
                'biTimeEfforts.user',
                'contracts',
                'events.event_type.biTags',
                'checklists.tasks',
                'departments',
                'users',
                'status',
            ])
            ->get();

        $rows = [];
        foreach ($projects as $project) {
            $rows[] = $this->buildProjectRow($project, $config['columns']);
        }

        $export = new BiProjectExport($rows, $config['columns']);
        $filename = 'bi-export-' . now()->format('Y-m-d_His') . '.xlsx';

        return $export->download($filename)->deleteFileAfterSend();
    }

    private function buildProjectRow(Project $project, array $columns): array
    {
        $biData = $project->biData;
        $derived = $this->biDerivedValuesService->getDerivedValues($project);
        $tagCounts = $this->biDerivedValuesService->getTagBasedCounts($project);

        $row = [];

        foreach ($columns as $column) {
            $row[$column] = match ($column) {
                'project_name' => $project->name,
                'project_state' => $project->status?->name ?? '',
                'visitors' => $this->getVisitorValue($biData, $project),
                'sold_tickets' => $this->getSoldTicketsValue($biData, $project),
                'revenue' => $this->getRevenueValue($biData, $project),
                'is_new_production' => $biData?->is_new_production ? 'Ja' : 'Nein',
                'is_co_production' => $biData?->is_co_production ? 'Ja' : 'Nein',
                'is_own_production' => $biData?->is_own_production ? 'Ja' : 'Nein',
                'is_germany_premiere' => $biData?->is_germany_premiere ? 'Ja' : 'Nein',
                'premiere_date' => $biData?->premiere_date?->format('d.m.Y') ?? '',
                'contract_count' => $derived['contract_count'],
                'event_count' => $derived['event_count'],
                'task_total' => $derived['task_total'],
                'task_open' => $derived['task_open'],
                'task_done' => $derived['task_done'],
                'document_count' => $derived['document_count'],
                'department_count' => $derived['department_count'],
                'user_count' => $derived['user_count'],
                'time_efforts' => $this->formatTimeEfforts($project),
                default => $this->resolveTagOrCustomColumn($column, $tagCounts, $project),
            };
        }

        return $row;
    }

    private function getVisitorValue($biData, Project $project): int|string
    {
        if (!$biData) {
            return '';
        }

        if ($biData->visitor_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->visitors_total ?? '';
        }

        return $project->biEventData->sum('visitors') ?? '';
    }

    private function getSoldTicketsValue($biData, Project $project): int|string
    {
        if (!$biData) {
            return '';
        }

        if ($biData->sold_tickets_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->sold_tickets_total ?? '';
        }

        return $project->biEventData->sum('sold_tickets') ?? '';
    }

    private function getRevenueValue($biData, Project $project): string
    {
        if (!$biData) {
            return '';
        }

        if ($biData->revenue_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->revenue_total ?? '';
        }

        return $project->biEventData->sum('revenue') ?? '';
    }

    private function formatTimeEfforts(Project $project): string
    {
        return $project->biTimeEfforts
            ->map(fn($effort) => $effort->label . ' (' . $effort->effort_bucket . ')')
            ->implode(', ');
    }

    private function resolveTagOrCustomColumn(string $column, array $tagCounts, Project $project): string
    {
        foreach ($tagCounts as $tagCount) {
            if ($column === 'tag_' . $tagCount['tag_id']) {
                return (string) $tagCount['count'];
            }
        }

        if (str_starts_with($column, 'custom_field_')) {
            $componentId = (int) str_replace('custom_field_', '', $column);

            return $this->getCustomFieldValue($project, $componentId);
        }

        return '';
    }

    private function getCustomFieldValue(Project $project, int $componentId): string
    {
        $value = ProjectComponentValue::where('project_id', $project->id)
            ->where('component_id', $componentId)
            ->first();

        if (!$value) {
            return '';
        }

        $data = $value->data ?? [];

        if (isset($data['text'])) {
            return $data['text'];
        }

        if (isset($data['checked'])) {
            return $data['checked'] ? 'Ja' : 'Nein';
        }

        if (isset($data['selected'])) {
            return $data['selected'];
        }

        return json_encode($data);
    }
}

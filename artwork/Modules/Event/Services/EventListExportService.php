<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Services\CacheService;
use Artwork\Core\Services\CollectionService;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Event\Abstracts\EventExportService;
use Artwork\Modules\Event\Exports\EventListXlsxExport;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventListExportService extends EventExportService
{
    public function __construct(
        private readonly EventListXlsxExport $eventListXlsxExport,
        EventSettingsService $eventSettingsService,
        EventRepository $eventRepository,
        Translator $translator,
        CacheService $cacheService,
        CarbonService $carbonService,
        CollectionService $collectionService,
        ProjectRepository $projectRepository,
    ) {
        parent::__construct(
            $eventSettingsService,
            $eventRepository,
            $cacheService,
            $collectionService,
            $carbonService,
            $translator,
            $projectRepository,
        );
    }

    protected function getPeriodExportTranslationKey(): string
    {
        return 'export.names.event-list-export-by-period';
    }

    protected function getProjectExportTranslationKey(): string
    {
        return 'export.names.event-list-export-by-projects';
    }

    protected function composeExport(): BinaryFileResponse
    {
        $desiredColumns = $this->getFromCachedData('desiredColumns');

        return $this->eventListXlsxExport
            ->setColumns(
                array_map(
                    function (string $column) {
                        return $this->translator->get('export.excel-event-list-export.columns.' . $column);
                    },
                    $desiredColumns
                )
            )
            ->setRows(
                $this->aggregateDesiredColumnsAndRowsFrom(
                    $desiredColumns,
                    $this->eventRepository->getEventsForExport($this->getFromCachedData())
                )
            )
            ->download($this->composeFilename())
            ->deleteFileAfterSend();
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    private function aggregateDesiredColumnsAndRowsFrom(array $desiredColumns, Collection $desiredEvents): array
    {
        $rows = [];

        foreach ($desiredEvents as $desiredEvent) {
            $project = $desiredEvent->getRelation('project');

            $row = [];
            foreach ($desiredColumns as $column) {
                $row[$column] = match ($column) {
                    'event_id' => $desiredEvent->getAttribute('id'),
                    'start_date' => $desiredEvent->getAttribute('start_time')->format('d.m.Y'),
                    'end_date' => $desiredEvent->getAttribute('end_time')->format('d.m.Y'),
                    'start_time' => $desiredEvent->getAttribute('start_time')->format('H:i'),
                    'end_time' => $desiredEvent->getAttribute('end_time')->format('H:i'),
                    'event_type' => $desiredEvent->getAttribute('event_type')->getAttribute('name'),
                    'event_name' => $desiredEvent->getAttribute('name'),
                    'event_status' => $this->eventSettingsService->get('enable_status', false) ?
                        $desiredEvent->getAttribute('eventStatus')?->getAttribute('name') ?? '' :
                        '',
                    'room' => $desiredEvent->getAttribute('room')?->getAttribute('name') ?? '',
                    'artists',
                    'project_name',
                    'project_team',
                    'project_properties' => $project ?
                        $this->aggregateDesiredProjectDataBy($column, $project) :
                        '',
                };
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function aggregateDesiredProjectDataBy(string $column, Project $project): string
    {
        return match ($column) {
            'artists' => $project->getAttribute('artists'),
            'project_name' => $project->getAttribute('name'),
            'project_team' => $project->getAttribute('users')
                ->map(
                    function (User|Freelancer|ServiceProvider $teamMember): string {
                        return match (get_class($teamMember)) {
                            ServiceProvider::class => $teamMember->getAttribute('provider_name'),
                            default =>
                                $teamMember->getAttribute('first_name') . ' ' .
                                $teamMember->getAttribute('last_name'),
                        };
                    }
                )
                ->implode(', '),
            'project_properties' => $project
                ->getAttribute('categories')
                ->concat($project->getAttribute('genres'))
                ->concat($project->getAttribute('sectors'))
                ->map(
                    function (Category|Genre|Sector $projectProperty): string {
                        return $projectProperty->getAttribute('name');
                    }
                )
                ->implode(', '),
        };
    }
}

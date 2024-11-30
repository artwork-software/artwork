<?php

namespace Artwork\Modules\Event\Services;

use App\Settings\EventSettings;
use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Event\Exports\EventListXlsxExport;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Sector\Models\Sector;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use DragonCode\Support\Helpers\Str;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Translation\Translator;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class EventListExportService
{
    public function __construct(
        private readonly EventSettings $eventSettings,
        private readonly EventListXlsxExport $eventListXlsxExport,
        private readonly CarbonService $carbonService,
        private readonly CacheManager $cacheManager,
        private readonly EventRepository $eventRepository,
        private readonly Str $str,
        private readonly Translator $translator
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function cacheRequestData(Collection $data): string
    {
        //cache forgets the item after 10 seconds, time enough to download
        $this->cacheManager->set($token = $this->str->random(128), $data, 10);

        return $token;
    }

    /**
     * @throws Throwable
     */
    public function getCachedRequestData(string $token): Collection
    {
        $data = $this->cacheManager->get($token);

        $this->cacheManager->delete($token);

        return $data;
    }

    /**
     * @throws Throwable
     * @return array<int, bool|EventListXlsxExport>
     */
    public function getConfiguredExport(string $token): array
    {
        $exportConfiguration = $this->getCachedRequestData($token);

        $desiredColumns = $exportConfiguration->get('desiredColumns');
        $desiredRows = $this->aggregateColumnsAndRowsFrom(
            $desiredColumns,
            $this->eventRepository->getEventsForEventListExportByFilters(
                $exportConfiguration
            )
        );
        $conditional = $exportConfiguration->get('conditional');

        return [
            $exportConfiguration->get('desiresTimespanExport') ?
                sprintf(
                    'zeitraum-%s-%s',
                    $this->carbonService->create($conditional['dateStart'])->format('d.m.Y'),
                    $this->carbonService->create($conditional['dateEnd'])->format('d.m.Y')
                ) :
                'projekte',
            $this->eventListXlsxExport
                ->setColumns(
                    array_map(
                        function (string $column) {
                            return $this->translator->get('export.excel-event-list-export.columns.' . $column);
                        },
                        $desiredColumns
                    )
                )
                ->setRows($desiredRows),
        ];
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    public function aggregateColumnsAndRowsFrom(array $desiredColumns, Collection $events): array
    {
        return $events->map(
            function (Event $event) use ($desiredColumns): array {
                $project = $event->getRelation('project');

                $row = [];
                foreach ($desiredColumns as $column) {
                    switch ($column) {
                        case 'event_id':
                            $row[$column] = $event->getAttribute('id');
                            break;
                        case 'project_name':
                            $row[$column] = $project ? $project->getAttribute('name') : '';
                            break;
                        case 'artists':
                            $row[$column] = $project ? $project->getAttribute('artists') : '';
                            break;
                        case 'start_date':
                            $row[$column] = $event->getAttribute('start_time')->format('d.m.Y');
                            break;
                        case 'end_date':
                            $row[$column] = $event->getAttribute('end_time')->format('d.m.Y');
                            break;
                        case 'start_time':
                            $row[$column] = $event->getAttribute('start_time')->format('H:i');
                            break;
                        case 'end_time':
                            $row[$column] = $event->getAttribute('end_time')->format('H:i');
                            break;
                        case 'event_type':
                            $row[$column] = $event->getAttribute('event_type')->getAttribute('name');
                            break;
                        case 'event_name':
                            $row[$column] = $event->getAttribute('name');
                            break;
                        case 'event_status':
                            $row[$column] = $this->eventSettings->enable_status ?
                                $event->getAttribute('eventStatus')?->getAttribute('name') ?? '' : '';
                            break;
                        case 'room':
                            $row[$column] = $event->getAttribute('room')?->getAttribute('name') ?? '';
                            break;
                        case 'project_team':
                            /** @var Collection $users */
                            $row[$column] = $project ?
                                $project
                                    ->getAttribute('users')
                                    ->map(
                                        function (User|Freelancer|ServiceProvider $teamMember): string {
                                            return match (get_class($teamMember)) {
                                                ServiceProvider::class => $teamMember
                                                    ->getAttribute('provider_name'),
                                                default => $teamMember->getAttribute('first_name') . ' ' .
                                                    $teamMember->getAttribute('last_name'),
                                            };
                                        }
                                    )
                                    ->implode(', ') :
                                    '';
                            break;
                        case 'project_properties':
                            $row[$column] = $project ?
                                $project
                                    ->getAttribute('categories')
                                    ->concat($project->getAttribute('genres'))
                                    ->concat($project->getAttribute('sectors'))
                                    ->map(
                                        function (Category|Genre|Sector $projectProperty): string {
                                            return $projectProperty->getAttribute('name');
                                        }
                                    )->implode(', ') : '';
                            break;
                    }
                }

                return $row;
            }
        )->toArray();
    }

    public function createXlsxExportFilename(string $filenameAddition): string
    {
        return sprintf(
            'event_list_export_%s_%s.xlsx',
            $filenameAddition,
            $this->carbonService->getNow()->format('d-m-Y_H_i_s')
        );
    }
}

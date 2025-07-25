<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Services\CacheService;
use Artwork\Core\Services\CollectionService;
use Artwork\Modules\Event\Abstracts\EventExportService;
use Artwork\Modules\Event\Exports\EventCalendarXlsxExport;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Artwork\Modules\Room\Repositories\RoomRepository;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Translation\Translator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventCalendarExportService extends EventExportService
{
    public function __construct(
        private readonly EventCalendarXlsxExport $eventCalendarXlsxExport,
        private readonly RoomRepository $roomRepository,
        private readonly UserService $userService,
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
        return 'export.names.event-calendar-export-by-period';
    }

    protected function getProjectExportTranslationKey(): string
    {
        return 'export.names.event-calendar-export-by-projects';
    }

    protected function composeExport(): BinaryFileResponse
    {
        $conditional = $this->getFromCachedData('conditional');

        $this->eventCalendarXlsxExport
            ->setDesiresTimespanExport(
                ($desiresTimespanExport = $this->getFromCachedData('desiresTimespanExport'))
            )
            ->setCreatedBy($this->aggregateCreatedBy())
            ->setRooms($this->getFilteredRooms())
            ->setEvents($this->eventRepository->getEventsForExport($this->getFromCachedData()));

        if ($desiresTimespanExport) {
            $this->eventCalendarXlsxExport
                ->setDateStart($this->carbonService->create($conditional['dateStart']))
                ->setDateEnd($this->carbonService->create($conditional['dateEnd']));
        } else {
            $projects = $this->projectRepository->getProjectsByIds($conditional['projects'], ['events']);

            $earliestStartDate = $latestEndDate = null;

            foreach ($projects as $project) {
                foreach ($project->getAttribute('events') as $event) {
                    if (!$earliestStartDate && !$latestEndDate) {
                        $earliestStartDate = $event->getAttribute('start_time');
                        $latestEndDate = $event->getAttribute('end_time');
                        continue;
                    }

                    $startTime = $event->getAttribute('start_time');
                    $endTime = $event->getAttribute('end_time');
                    if ($startTime < $earliestStartDate) {
                        $earliestStartDate = $startTime;
                    }

                    if ($endTime > $latestEndDate) {
                        $latestEndDate = $endTime;
                    }
                }
            }

            $this->eventCalendarXlsxExport
                ->setProjects(
                    array_map(
                        function (int $project): string {
                            return $this->projectRepository->findOrFail($project)->getAttribute('name');
                        },
                        $conditional['projects']
                    )
                )
                ->setDateStart($earliestStartDate)
                ->setDateEnd($latestEndDate);
        }

        return $this
            ->eventCalendarXlsxExport
            ->download($this->composeFilename())
            ->deleteFileAfterSend();
    }

    private function getFilteredRooms(): Collection
    {
        $filter = $this->getFromCachedData('filter');

        $desiredRoomIds = $filter['rooms'];
        $desiredRoomCategories = $filter['roomCategories'];
        $desiredRoomAttributes = $filter['roomAttributes'];
        $desiredAreaIds = $filter['areas'];

        return $this->roomRepository->getFilteredRoomsBy(
            count($desiredRoomIds) > 0 ? $desiredRoomIds : null,
            count($desiredRoomAttributes) > 0 ? $desiredRoomAttributes : null,
            count($desiredAreaIds) > 0 ? $desiredAreaIds : null,
            count($desiredRoomCategories) > 0 ? $desiredRoomCategories : null,
        );
    }

    private function aggregateCreatedBy(): string
    {
        return sprintf(
            "%s %s",
            ($user = $this->userService->getAuthUser())->getAttribute('first_name'),
            $user->getAttribute('last_name')
        );
    }
}

<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Services\CacheService;
use Artwork\Core\Services\CollectionService;
use Artwork\Modules\Event\Abstracts\EventExportService;
use Artwork\Modules\Event\Exports\EventCalendarXlsxExport;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\EventSettings\Services\EventSettingsService;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Translation\Translator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventCalendarExportService extends EventExportService
{
    public function __construct(
        private readonly EventCalendarXlsxExport $eventCalendarXlsxExport,
        private readonly RoomService $roomService,
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
            ->setRooms($this->roomService->getAllRoomsWithoutTrashed())
            ->setEvents($this->eventRepository->getEventsForEventListExportByFilters($this->getFromCachedData()));

        if ($desiresTimespanExport) {
            $this->eventCalendarXlsxExport
                ->setDateStart($conditional['dateStart'])
                ->setDateEnd($conditional['dateEnd']);
        } else {
            $this->eventCalendarXlsxExport
                ->setProjects(
                    array_map(
                        function (int $project): string {
                            return $this->projectRepository->findOrFail($project)->getAttribute('name');
                        },
                        $conditional['projects']
                    )
                );
        }

        return $this
            ->eventCalendarXlsxExport
            ->download($this->composeFilename())
            ->deleteFileAfterSend();
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

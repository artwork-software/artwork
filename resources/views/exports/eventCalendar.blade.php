@php
    use Artwork\Modules\Event\Services\EventCalendarExportBladeTemplateService;
        /**
         * @var bool $desiresTimespanExport
         * @var string $createdBy
         * @var array $rooms
         * @var array $events
         * @var array $projects
         * @var string $dateStart
         * @var string $dateEnd
         */
        /** @var EventCalendarExportBladeTemplateService $eventCalendarExportBladeTemplateService */
        $eventCalendarExportBladeTemplateService = app()->make(EventCalendarExportBladeTemplateService::class);
        $eventCalendarExportBladeTemplateService->initialize(
            $desiresTimespanExport,
            $createdBy,
            $rooms,
            $events,
            $projects,
            $dateStart,
            $dateEnd
        )->render();
@endphp

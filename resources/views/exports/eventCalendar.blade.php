@php
    use Artwork\Modules\Event\Services\EventCalendarExportBladeTemplateService;
    use Illuminate\Database\Eloquent\Collection;
        /**
         * @var bool $desiresTimespanExport
         * @var string $createdBy
         * @var Collection $rooms
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

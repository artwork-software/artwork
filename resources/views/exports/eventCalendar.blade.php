@php
    use Artwork\Modules\Event\Services\EventCalendarExportBladeTemplateService;
    use Illuminate\Database\Eloquent\Collection;
    use Carbon\Carbon;
        /**
         * @var bool $desiresTimespanExport
         * @var string $createdBy
         * @var Collection $rooms
         * @var array $events
         * @var array $projects
         * @var Carbon $dateStart
         * @var Carbon $dateEnd
         */
        /** @var EventCalendarExportBladeTemplateService $eventCalendarExportBladeTemplateService */
        $eventCalendarExportBladeTemplateService = app()->make(EventCalendarExportBladeTemplateService::class);
        $eventCalendarExportBladeTemplateService->render(
            $desiresTimespanExport,
            $createdBy,
            $rooms,
            $events,
            $dateStart,
            $dateEnd,
            $projects,
        );
@endphp

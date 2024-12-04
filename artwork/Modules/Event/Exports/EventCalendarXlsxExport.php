<?php

namespace Artwork\Modules\Event\Exports;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventCalendarXlsxExport implements FromView, WithStyles
{
    use Exportable;

    private bool $desiresTimespanExport;

    private string $createdBy;

    private array $rooms;

    private Collection $events;

    private string $dateStart;

    private string $dateEnd;

    private array $projects;

    public function __construct(private readonly ViewFactory $viewFactory)
    {
    }

    public function view(): View
    {
        return $this->viewFactory->make(
            'exports.eventCalendar',
            [
                'desiresTimespanExport' => $this->desiresTimespanExport,
                'createdBy' => $this->createdBy,
                'rooms' => $this->rooms,
                'events' => $this->events,
                'projects' => $this->projects ?? null,
                'dateStart' => $this->dateStart ?? null,
                'dateEnd' => $this->dateEnd ?? null,
            ]
        );
    }

    public function setDesiresTimespanExport(bool $desiresTimespanExport): self
    {
        $this->desiresTimespanExport = $desiresTimespanExport;

        return $this;
    }

    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function setRooms(array $rooms): self
    {
        $this->rooms = $rooms;

        return $this;
    }

    public function setEvents(Collection $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function setDateStart(string $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function setDateEnd(string $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * @param array $projects
     */
    public function setProjects(array $projects): void
    {
        $this->projects = $projects;
    }

    /**
     * @return array<int, array<string, array<string, mixed>>>
     */
    //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
    public function styles(Worksheet $sheet): array
    {
        return [
            //first row bold
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}

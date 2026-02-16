<?php

namespace Artwork\Modules\Calendar\DTO;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PdfEventDTO
{
    public int $id;
    public string $start;
    public string $end;
    public ?string $eventName;
    public bool $allDay;
    public int $roomId;
    public ?object $eventType;
    public ?object $project;
    public array $daysOfEvent;

    public function __construct(
        int $id,
        string $startTime,
        string $endTime,
        ?string $eventName,
        bool $allDay,
        int $roomId,
        ?object $eventType,
        ?object $project,
    ) {
        $this->id = $id;
        $this->start = Carbon::parse($startTime)->format('Y-m-d H:i');
        $this->end = Carbon::parse($endTime)->format('Y-m-d H:i');
        $this->eventName = $eventName;
        $this->allDay = $allDay;
        $this->roomId = $roomId;
        $this->eventType = $eventType;
        $this->project = $project;
        $this->daysOfEvent = collect(CarbonPeriod::create($startTime, $endTime))
            ->map(fn($d) => $d->format('d.m.Y'))
            ->toArray();
    }
}

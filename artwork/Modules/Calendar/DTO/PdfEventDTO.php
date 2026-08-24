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
    public ?string $artistNames;
    public ?string $mainCategoryColor;
    public ?string $description;
    public ?object $eventStatus;
    public ?string $createdBy;
    public ?string $projectStatus;
    /** @var array<int, string>|null */
    public ?array $projectLeaders;
    /** Bewusst snake_case: die PDF-Blades lesen `$event->admission_time`. */
    public ?string $admission_time;

    public function __construct(
        int $id,
        string $startTime,
        string $endTime,
        ?string $eventName,
        bool $allDay,
        int $roomId,
        ?object $eventType,
        ?object $project,
        ?string $artistNames = null,
        ?string $mainCategoryColor = null,
        ?string $description = null,
        ?object $eventStatus = null,
        ?string $createdBy = null,
        ?string $projectStatus = null,
        ?array $projectLeaders = null,
        ?string $admissionTime = null,
    ) {
        $this->id = $id;
        $this->start = Carbon::parse($startTime)->format('Y-m-d H:i');
        $this->end = Carbon::parse($endTime)->format('Y-m-d H:i');
        $this->eventName = $eventName;
        $this->allDay = $allDay;
        $this->roomId = $roomId;
        $this->eventType = $eventType;
        $this->project = $project;
        $this->artistNames = $artistNames;
        $this->mainCategoryColor = $mainCategoryColor;
        $this->description = $description;
        $this->eventStatus = $eventStatus;
        $this->createdBy = $createdBy;
        $this->projectStatus = $projectStatus;
        $this->projectLeaders = $projectLeaders;
        $this->admission_time = $admissionTime;
        $this->daysOfEvent = collect(CarbonPeriod::create($startTime, $endTime))
            ->map(fn($d) => $d->format('d.m.Y'))
            ->toArray();
    }
}

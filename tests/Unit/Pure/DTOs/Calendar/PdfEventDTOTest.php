<?php

namespace Tests\Unit\Pure\DTOs\Calendar;

use Artwork\Modules\Calendar\DTO\PdfEventDTO;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PdfEventDTOTest extends TestCase
{
    #[Test]
    public function it_formats_start_and_end_dates_to_y_m_d_h_i(): void
    {
        $dto = new PdfEventDTO(
            id: 1,
            startTime: '2026-05-10 09:00:00',
            endTime: '2026-05-10 11:30:00',
            eventName: 'Stage Check',
            allDay: false,
            roomId: 4,
            eventType: null,
            project: null,
        );

        $this->assertSame('2026-05-10 09:00', $dto->start);
        $this->assertSame('2026-05-10 11:30', $dto->end);
    }

    #[Test]
    public function it_computes_days_of_event_array(): void
    {
        $dto = new PdfEventDTO(
            id: 1,
            startTime: '2026-05-10 09:00:00',
            endTime: '2026-05-12 11:30:00',
            eventName: 'Multi-day Event',
            allDay: false,
            roomId: 4,
            eventType: null,
            project: null,
        );

        $this->assertSame(['10.05.2026', '11.05.2026', '12.05.2026'], $dto->daysOfEvent);
    }

    #[Test]
    public function it_persists_all_optional_fields(): void
    {
        $eventType = (object) ['id' => 1];
        $project = (object) ['id' => 99];

        $dto = new PdfEventDTO(
            id: 5,
            startTime: '2026-01-01 00:00:00',
            endTime: '2026-01-01 23:59:00',
            eventName: 'New Year',
            allDay: true,
            roomId: 1,
            eventType: $eventType,
            project: $project,
            artistNames: 'Some Artist',
            mainCategoryColor: '#abc',
        );

        $this->assertSame(5, $dto->id);
        $this->assertSame($eventType, $dto->eventType);
        $this->assertSame($project, $dto->project);
        $this->assertSame('Some Artist', $dto->artistNames);
        $this->assertSame('#abc', $dto->mainCategoryColor);
        $this->assertTrue($dto->allDay);
    }

    #[Test]
    public function event_name_can_be_null(): void
    {
        $dto = new PdfEventDTO(
            id: 1,
            startTime: '2026-05-10 09:00:00',
            endTime: '2026-05-10 11:00:00',
            eventName: null,
            allDay: false,
            roomId: 1,
            eventType: null,
            project: null,
        );

        $this->assertNull($dto->eventName);
    }
}

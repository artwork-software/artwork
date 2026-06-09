<?php

namespace Tests\Unit\Modules\Calendar\DTO;

use Artwork\Modules\Calendar\DTO\EventDTOWithVerifications;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Spatie\LaravelData\Lazy;

final class EventDTOWithVerificationsTest extends TestCase
{
    #[Test]
    public function event_status_accepts_a_lazy_value_without_throwing(): void
    {
        // Regression: fromModel() übergibt für eventStatus ein Lazy::inertia(...),
        // wenn ein Event-Status existiert, aber use_event_status_color deaktiviert ist.
        // Früher war der Konstruktor-Parameter als ?array typisiert → TypeError
        // ("Argument #20 ($eventStatus) must be of type ?array, ...InertiaLazy given").
        $dto = new EventDTOWithVerifications(
            id: 1,
            start: '2026-06-08 14:30',
            end: '2026-06-08 23:00',
            eventName: 'Test',
            description: null,
            project: null,
            eventType: null,
            shifts: null,
            allDay: false,
            roomId: null,
            roomName: null,
            startHour: 0,
            minutesFormStartHourToStart: 0,
            eventLengthInHours: 0,
            created_by: null,
            is_series: false,
            eventProperties: null,
            occupancy_option: false,
            declinedRoomId: null,
            eventStatus: Lazy::inertia(fn () => ['id' => 1, 'color' => '#ffffff']),
            subEvents: null,
            option_string: null,
        );

        $this->assertInstanceOf(Lazy::class, $dto->eventStatus);
    }

    #[Test]
    public function event_status_still_accepts_array_and_null(): void
    {
        $withArray = new EventDTOWithVerifications(
            id: 1,
            start: '2026-06-08 14:30',
            end: '2026-06-08 23:00',
            eventName: 'Test',
            description: null,
            project: null,
            eventType: null,
            shifts: null,
            allDay: false,
            roomId: null,
            roomName: null,
            startHour: 0,
            minutesFormStartHourToStart: 0,
            eventLengthInHours: 0,
            created_by: null,
            is_series: false,
            eventProperties: null,
            occupancy_option: false,
            declinedRoomId: null,
            eventStatus: ['id' => 1, 'color' => '#ffffff'],
            subEvents: null,
            option_string: null,
        );

        $this->assertIsArray($withArray->eventStatus);

        $withNull = new EventDTOWithVerifications(
            id: 2,
            start: '2026-06-08 14:30',
            end: '2026-06-08 23:00',
            eventName: 'Test',
            description: null,
            project: null,
            eventType: null,
            shifts: null,
            allDay: false,
            roomId: null,
            roomName: null,
            startHour: 0,
            minutesFormStartHourToStart: 0,
            eventLengthInHours: 0,
            created_by: null,
            is_series: false,
            eventProperties: null,
            occupancy_option: false,
            declinedRoomId: null,
            eventStatus: null,
            subEvents: null,
            option_string: null,
        );

        $this->assertNull($withNull->eventStatus);
    }
}

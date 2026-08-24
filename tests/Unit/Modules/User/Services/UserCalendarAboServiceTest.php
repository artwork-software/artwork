<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarAbo;
use Artwork\Modules\User\Services\UserCalendarAboService;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use Spatie\IcalendarGenerator\Components\Calendar;
use Tests\TestCase;

final class UserCalendarAboServiceTest extends TestCase
{
    private UserCalendarAboService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserCalendarAboService::class);
    }

    private function defaultPayload(): array
    {
        return [
            'date_range' => false,
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-31',
            'specific_event_types' => false,
            'event_types' => [],
            'specific_rooms' => false,
            'selected_rooms' => [],
            'specific_areas' => false,
            'selected_areas' => [],
            'enable_notification' => false,
            'notification_time' => 0,
            'notification_time_unit' => 'minutes',
        ];
    }

    #[Test]
    public function create_persists_calendar_abo_for_user(): void
    {
        $user = User::factory()->create();

        $this->service->create($this->defaultPayload(), $user->id);

        $this->assertDatabaseHas('user_calendar_abos', [
            'user_id' => $user->id,
            'start_date' => '2024-05-01',
            'end_date' => '2024-05-31',
        ]);
    }

    #[Test]
    public function update_by_request_modifies_calendar_abo(): void
    {
        $user = User::factory()->create();
        $this->service->create($this->defaultPayload(), $user->id);
        $abo = UserCalendarAbo::first();

        $this->service->updateByRequest(
            ['enable_notification' => true, 'notification_time' => 15],
            $abo
        );

        $fresh = $abo->fresh();
        $this->assertTrue((bool) $fresh->enable_notification);
        $this->assertSame(15, $fresh->notification_time);
    }

    #[Test]
    public function get_filtered_events_query_returns_builder(): void
    {
        $user = User::factory()->create();
        $this->service->create($this->defaultPayload(), $user->id);
        $abo = UserCalendarAbo::first();

        $query = $this->service->getFilteredEventsQuery($abo);

        $this->assertInstanceOf(Builder::class, $query);
    }

    #[Test]
    public function add_event_to_calendar_omits_organizer(): void
    {
        $creator = User::factory()->create([
            'first_name' => 'Orga',
            'last_name' => 'Nisator',
            'email' => 'orga@example.test',
        ]);
        $event = Event::factory()->create([
            'user_id' => $creator->id,
            'description' => 'Technikprobe',
        ]);

        $calendar = Calendar::create('Test');
        $this->service->addEventToCalendar($calendar, $event->fresh());

        $ics = str_replace("\r\n ", '', $calendar->get());

        // Keine ORGANIZER-Zeile: sonst haengt im Zielkalender eine fremde
        // Mailadresse am Termin und Clients behandeln ihn als Einladung
        $this->assertStringNotContainsString('ORGANIZER', $ics);
        $this->assertStringNotContainsString('ATTENDEE', $ics);
        $this->assertStringNotContainsString('orga@example.test', $ics);
        $this->assertStringContainsString('Technikprobe', $ics);
        $this->assertStringContainsString('Organisation: Orga Nisator', $ics);
    }
}

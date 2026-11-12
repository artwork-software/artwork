<?php

namespace Tests\Feature\Modules\Event;

use App\Settings\EventSettings;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Event\Repositories\EventRepository;
use Artwork\Modules\Event\Services\EventListExportService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Excel "Export nach Zeitraum": jeder Termin, der den Zeitraum berührt, gehört in den Export. Vorher zählte
 * nur, ob Beginn oder Ende im Zeitraum liegt; Termine, die den ganzen Zeitraum überspannen, fehlten.
 * Außerdem sind geplante Termine in der Terminliste in der Spalte "Terminstatus" gekennzeichnet.
 */
final class EventListExportPeriodTest extends FeatureTestCase
{
    #[Test]
    public function the_period_export_contains_events_spanning_the_whole_period(): void
    {
        $this->actingAsAdmin();
        $spanning = $this->event('2026-09-01 10:00:00', '2026-09-30 18:00:00');
        $startingInside = $this->event('2026-09-12 09:00:00', '2026-09-20 09:00:00');
        $endingInside = $this->event('2026-09-05 09:00:00', '2026-09-10 09:00:00');
        $inside = $this->event('2026-09-11 09:00:00', '2026-09-11 11:00:00');
        $before = $this->event('2026-09-08 09:00:00', '2026-09-09 23:00:00');
        $after = $this->event('2026-09-13 00:00:00', '2026-09-14 09:00:00');

        $ids = app(EventRepository::class)
            ->getEventsForExport($this->periodConfiguration('2026-09-10', '2026-09-12'))
            ->pluck('id');

        $this->assertContains($spanning->id, $ids);
        $this->assertContains($startingInside->id, $ids);
        $this->assertContains($endingInside->id, $ids);
        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($before->id, $ids);
        $this->assertNotContains($after->id, $ids);
    }

    #[Test]
    public function the_period_export_contains_events_without_end_time_that_start_in_the_period(): void
    {
        $this->actingAsAdmin();
        $startingInside = $this->eventWithoutEndTime('2026-09-11 09:00:00');
        $startingBefore = $this->eventWithoutEndTime('2026-09-09 09:00:00');
        $startingAfter = $this->eventWithoutEndTime('2026-09-13 09:00:00');

        $ids = app(EventRepository::class)
            ->getEventsForExport($this->periodConfiguration('2026-09-10', '2026-09-12'))
            ->pluck('id');

        $this->assertContains($startingInside->id, $ids);
        $this->assertNotContains($startingBefore->id, $ids);
        $this->assertNotContains($startingAfter->id, $ids);
    }

    #[Test]
    public function planned_events_are_marked_in_the_event_status_column(): void
    {
        $this->setStatusEnabled(true);
        $status = EventStatus::factory()->create(['name' => 'Bestätigt']);
        $service = app(EventListExportService::class);
        $planned = __('export.excel-event-list-export.planned');

        $fixed = Event::factory()->create(['is_planning' => false, 'event_status_id' => $status->id]);
        $plannedWithStatus = Event::factory()->create(['is_planning' => true, 'event_status_id' => $status->id]);
        $plannedWithoutStatus = Event::factory()->create(['is_planning' => true, 'event_status_id' => null]);

        $this->assertSame('Bestätigt', $service->eventStatusText($fixed));
        $this->assertSame('Bestätigt (' . $planned . ')', $service->eventStatusText($plannedWithStatus));
        $this->assertSame($planned, $service->eventStatusText($plannedWithoutStatus));

        $this->setStatusEnabled(false);
        $service = app(EventListExportService::class);
        $this->assertSame('', $service->eventStatusText($fixed));
        $this->assertSame($planned, $service->eventStatusText($plannedWithStatus));
    }

    private function event(string $start, string $end): Event
    {
        return Event::factory()->create(['start_time' => $start, 'end_time' => $end, 'is_planning' => false]);
    }

    /**
     * Ältere Termine haben teils keine Endzeit; das Model selbst speichert so keinen Termin mehr.
     */
    private function eventWithoutEndTime(string $start): Event
    {
        $event = $this->event($start, $start);
        Event::query()->whereKey($event->id)->update(['end_time' => null]);

        return $event;
    }

    /**
     * @return Collection<string, mixed>
     */
    private function periodConfiguration(string $start, string $end): Collection
    {
        return collect([
            'desiresTimespanExport' => true,
            'conditional' => ['dateStart' => $start, 'dateEnd' => $end],
            'filter' => [],
        ]);
    }

    private function setStatusEnabled(bool $enabled): void
    {
        $settings = app(EventSettings::class);
        $settings->enable_status = $enabled;
        $settings->save();
    }
}

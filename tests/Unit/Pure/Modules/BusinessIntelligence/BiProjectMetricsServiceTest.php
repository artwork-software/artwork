<?php

namespace Tests\Unit\Pure\Modules\BusinessIntelligence;

use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategoryValue;
use Artwork\Modules\BusinessIntelligence\Models\BiEventData;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectRoomCapacity;
use Artwork\Modules\BusinessIntelligence\Services\BiProjectMetricsService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

/**
 * DB-lose Tests: der Service arbeitet ausschließlich auf geladenen Relationen,
 * die hier per setRelation() gestellt werden.
 */
final class BiProjectMetricsServiceTest extends UnitTestCase
{
    private BiProjectMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BiProjectMetricsService();
        // DB-los: beide KPI-Tags gelten als Terminarten zugeordnet
        $this->service->primeLinkedKpiTags(['performance', 'event_day']);
    }

    /**
     * @param array<string, mixed> $biDataAttributes
     */
    private function makeProject(
        ?array $biDataAttributes,
        array $events = [],
        array $eventData = [],
        array $roomCapacities = [],
        array $categoryValues = []
    ): Project {
        $project = new Project();
        $project->id = 1;

        if ($biDataAttributes !== null) {
            $biData = new BiProjectData();
            $biData->forceFill($biDataAttributes);
            $project->setRelation('biData', $biData);
        } else {
            $project->setRelation('biData', null);
        }

        $project->setRelation('events', new Collection($events));
        $project->setRelation('biEventData', new Collection($eventData));
        $project->setRelation('biRoomCapacities', new Collection($roomCapacities));
        $project->setRelation('biAudienceCategoryValues', new Collection($categoryValues));
        $project->setRelation('planBiData', null);
        $project->setRelation('planBiEventData', new Collection([]));

        return $project;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function withPlanData(Project $project, array $attributes, array $planEventData = []): Project
    {
        $planData = new BiProjectData();
        $planData->forceFill(array_merge(['scope' => 'plan'], $attributes));
        $project->setRelation('planBiData', $planData);
        $project->setRelation('planBiEventData', new Collection($planEventData));

        return $project;
    }

    /**
     * Stellt die drei Default-Kategorien (1=full, 2=reduced, 3=free) in den
     * Service-Memo-Cache, damit kein DB-Zugriff nötig ist.
     */
    private function primeDefaultCategories(): void
    {
        $categories = (new Collection([
            ['id' => 1, 'pricing_type' => 'full', 'name' => 'Vollzahler*innen'],
            ['id' => 2, 'pricing_type' => 'reduced', 'name' => 'Ermäßigt'],
            ['id' => 3, 'pricing_type' => 'free', 'name' => 'Freikarten'],
        ]))->map(function (array $attributes): BiAudienceCategory {
            $category = new BiAudienceCategory();
            $category->forceFill($attributes);

            return $category;
        });

        $this->service->primeCategories($categories);
    }

    private function makeCategoryValue(
        int $categoryId,
        ?int $eventId,
        ?int $quantity,
        string $scope = 'actual'
    ): BiAudienceCategoryValue {
        $value = new BiAudienceCategoryValue();
        $value->forceFill([
            'project_id' => 1,
            'bi_audience_category_id' => $categoryId,
            'event_id' => $eventId,
            'scope' => $scope,
            'quantity' => $quantity,
        ]);

        return $value;
    }

    private function makeEvent(
        int $id,
        string $start,
        ?string $end = null,
        ?Room $room = null,
        array $tagNames = []
    ): Event {
        $event = new Event();
        $event->id = $id;
        $event->start_time = Carbon::parse($start);
        $event->end_time = $end !== null ? Carbon::parse($end) : Carbon::parse($start)->addHours(2);
        $event->setRelation('room', $room);

        $eventType = new EventType();
        $eventType->setRelation('biTags', (new Collection($tagNames))->map(function (string $name): BiEventTypeTag {
            $tag = new BiEventTypeTag();
            $tag->name = $name;
            $tag->name_de = $name;
            // Kennzahl-Steuerung läuft über die Rolle, nicht über den Namen
            $tag->kpi_role = match (mb_strtolower($name)) {
                'vorstellung' => BiEventTypeTag::KPI_ROLE_PERFORMANCE,
                'veranstaltungstag' => BiEventTypeTag::KPI_ROLE_EVENT_DAY,
                default => null,
            };

            return $tag;
        }));
        $event->setRelation('event_type', $eventType);

        return $event;
    }

    private function makeEventData(Event $event, ?int $visitors, ?int $soldTickets, ?float $revenue): BiEventData
    {
        $data = new BiEventData();
        $data->forceFill([
            'project_id' => 1,
            'event_id' => $event->id,
            'visitors' => $visitors,
            'sold_tickets' => $soldTickets,
            'revenue' => $revenue,
        ]);
        $data->setRelation('event', $event);

        return $data;
    }

    private function makeRoom(int $id, ?int $capacity): Room
    {
        $room = new Room();
        $room->id = $id;
        $room->capacity = $capacity;

        return $room;
    }

    #[Test]
    public function total_mode_returns_totals_regardless_of_range(): void
    {
        $project = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => 500,
            'sold_tickets_mode' => 'total',
            'sold_tickets_total' => 450,
            'revenue_mode' => 'total',
            'revenue_total' => 9000.50,
        ]);

        $from = Carbon::parse('2030-01-01');
        $to = Carbon::parse('2030-12-31');

        self::assertSame(500, $this->service->visitors($project, $from, $to));
        self::assertSame(450, $this->service->soldTickets($project, $from, $to));
        self::assertSame(9000.50, $this->service->revenue($project, $from, $to));
    }

    #[Test]
    public function per_event_mode_sums_event_data_within_range_only(): void
    {
        $inRange = $this->makeEvent(1, '2026-06-10 19:00');
        $outOfRange = $this->makeEvent(2, '2025-06-10 19:00');

        $project = $this->makeProject(
            ['visitor_mode' => 'per_event'],
            [$inRange, $outOfRange],
            [
                $this->makeEventData($inRange, 120, null, null),
                $this->makeEventData($outOfRange, 999, null, null),
            ]
        );

        $visitors = $this->service->visitors(
            $project,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        self::assertSame(120, $visitors);
    }

    #[Test]
    public function per_event_mode_returns_null_when_nothing_recorded_but_zero_when_zero_recorded(): void
    {
        $event = $this->makeEvent(1, '2026-06-10 19:00');

        $nothingRecorded = $this->makeProject(
            ['visitor_mode' => 'per_event'],
            [$event],
            [$this->makeEventData($event, null, null, null)]
        );
        self::assertNull($this->service->visitors($nothingRecorded));

        $zeroRecorded = $this->makeProject(
            ['visitor_mode' => 'per_event'],
            [$event],
            [$this->makeEventData($event, 0, null, null)]
        );
        self::assertSame(0, $this->service->visitors($zeroRecorded));
    }

    #[Test]
    public function not_applicable_flags_null_out_the_metric(): void
    {
        $project = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => 500,
            'visitors_not_applicable' => true,
            'sold_tickets_mode' => 'total',
            'sold_tickets_total' => 450,
            'sold_tickets_not_applicable' => true,
            'revenue_mode' => 'total',
            'revenue_total' => 9000,
            'revenue_not_applicable' => true,
        ]);

        self::assertNull($this->service->visitors($project));
        self::assertNull($this->service->soldTickets($project));
        self::assertNull($this->service->revenue($project));
    }

    #[Test]
    public function visitors_estimate_falls_back_to_sold_tickets(): void
    {
        $project = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => null,
            'sold_tickets_mode' => 'total',
            'sold_tickets_total' => 321,
        ]);

        $result = $this->service->visitorsWithEstimate($project);

        self::assertSame(321, $result['value']);
        self::assertTrue($result['estimated']);
    }

    #[Test]
    public function visitors_estimate_is_not_used_when_visitors_are_recorded_or_flags_forbid_it(): void
    {
        $recorded = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => 100,
            'sold_tickets_mode' => 'total',
            'sold_tickets_total' => 321,
        ]);
        $result = $this->service->visitorsWithEstimate($recorded);
        self::assertSame(100, $result['value']);
        self::assertFalse($result['estimated']);

        $notApplicable = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => null,
            'visitors_not_applicable' => true,
            'sold_tickets_mode' => 'total',
            'sold_tickets_total' => 321,
        ]);
        $result = $this->service->visitorsWithEstimate($notApplicable);
        self::assertNull($result['value']);
        self::assertFalse($result['estimated']);
    }

    #[Test]
    public function average_price_handles_null_and_zero_sold_tickets(): void
    {
        self::assertNull($this->service->averagePrice(null, 10));
        self::assertNull($this->service->averagePrice(100.0, null));
        self::assertNull($this->service->averagePrice(100.0, 0));
        self::assertSame(12.5, $this->service->averagePrice(100.0, 8));
    }

    #[Test]
    public function occupancy_rate_handles_missing_values(): void
    {
        self::assertNull($this->service->occupancyRate(null, 100));
        self::assertNull($this->service->occupancyRate(50, 0));
        self::assertSame(50.0, $this->service->occupancyRate(50, 100));
        self::assertSame(33.3, $this->service->occupancyRate(100, 300));
    }

    #[Test]
    public function seats_capacity_uses_room_capacity_with_override_per_performance(): void
    {
        $room = $this->makeRoom(7, 200);
        $eventA = $this->makeEvent(1, '2026-06-10 19:00', null, $room, ['Vorstellung']);
        $eventB = $this->makeEvent(2, '2026-06-11 19:00', null, $room, ['Vorstellung']);

        $override = new BiProjectRoomCapacity();
        $override->forceFill(['project_id' => 1, 'room_id' => 7, 'capacity_override' => 150]);

        $project = $this->makeProject(
            ['sold_tickets_mode' => 'per_event'],
            [$eventA, $eventB],
            [],
            [$override]
        );

        // Raum zählt je Vorstellung, Override ersetzt die Raumkapazität.
        self::assertSame(300, $this->service->seatsCapacity($project));
    }

    #[Test]
    public function seats_capacity_ignores_date_range_in_total_mode(): void
    {
        $room = $this->makeRoom(7, 100);
        $event2025 = $this->makeEvent(1, '2025-06-10 19:00', null, $room, ['Vorstellung']);
        $event2026 = $this->makeEvent(2, '2026-06-10 19:00', null, $room, ['Vorstellung']);

        $project = $this->makeProject(
            ['sold_tickets_mode' => 'total'],
            [$event2025, $event2026]
        );

        $capacity = $this->service->seatsCapacity(
            $project,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        self::assertSame(200, $capacity);
    }

    #[Test]
    public function performances_counts_only_tagged_events_without_fallback(): void
    {
        $tagged = $this->makeEvent(1, '2026-06-10 19:00', null, null, ['Vorstellung']);
        $untagged = $this->makeEvent(2, '2026-06-11 19:00');

        $withTags = $this->makeProject(null, [$tagged, $untagged]);
        self::assertSame(1, $this->service->performances($withTags));

        // Tag zugeordnet, aber dieses Projekt hat keine Vorstellung → echte 0
        $withoutTaggedEvents = $this->makeProject(null, [
            $this->makeEvent(1, '2026-06-10 19:00'),
            $this->makeEvent(2, '2026-06-11 19:00'),
        ]);
        self::assertSame(0, $this->service->performances($withoutTaggedEvents));
    }

    #[Test]
    public function tag_kpis_are_null_and_capacity_zero_while_kpi_tags_are_unlinked(): void
    {
        $room = new Room();
        $room->id = 1;
        $room->capacity = 200;

        $project = $this->makeProject(null, [
            $this->makeEvent(1, '2026-06-10 19:00', null, $room, ['Vorstellung', 'Veranstaltungstag']),
            $this->makeEvent(2, '2026-06-11 19:00', null, $room),
        ]);

        // Kein Fallback auf "alle Termine": ohne Terminart-Zuordnung bleibt alles leer
        $this->service->primeLinkedKpiTags([]);
        self::assertNull($this->service->performances($project));
        self::assertNull($this->service->eventDays($project));
        self::assertSame(0, $this->service->seatsCapacity($project));
        self::assertNull($this->service->occupancyRate(50, $this->service->seatsCapacity($project)));

        $summary = $this->service->summary($project);
        self::assertFalse($summary['performance_tag_linked']);
        self::assertFalse($summary['event_day_tag_linked']);
        self::assertNull($summary['performances']);

        // Nur der Vorstellungs-Tag zugeordnet → Kapazität aus der EINEN Vorstellung
        $this->service->primeLinkedKpiTags(['performance']);
        self::assertSame(1, $this->service->performances($project));
        self::assertNull($this->service->eventDays($project));
        self::assertSame(200, $this->service->seatsCapacity($project));
    }

    #[Test]
    public function event_days_counts_distinct_start_days(): void
    {
        $project = $this->makeProject(null, [
            $this->makeEvent(1, '2026-06-10 15:00', null, null, ['Veranstaltungstag']),
            $this->makeEvent(2, '2026-06-10 20:00', null, null, ['Veranstaltungstag']),
            $this->makeEvent(3, '2026-06-11 20:00', null, null, ['Veranstaltungstag']),
        ]);

        self::assertSame(2, $this->service->eventDays($project));
    }

    #[Test]
    public function category_sums_in_total_mode_use_only_total_rows(): void
    {
        $this->primeDefaultCategories();

        $project = $this->makeProject(
            ['sold_tickets_mode' => 'total'],
            [],
            [],
            [],
            [
                $this->makeCategoryValue(1, null, 100),
                $this->makeCategoryValue(2, null, 40),
                $this->makeCategoryValue(3, null, 25),
                // Terminwert darf im TOTAL-Modus nicht zählen:
                $this->makeCategoryValue(1, 99, 999),
                // Plan-Werte zählen nie ins Ist:
                $this->makeCategoryValue(1, null, 500, 'plan'),
            ]
        );

        self::assertSame(
            ['full' => 100, 'reduced' => 40, 'free' => 25],
            $this->service->categorySums($project)
        );
        self::assertSame(165, $this->service->ticketsIssued($project));
        self::assertSame(140, $this->service->paidTicketsFromCategories($project));
    }

    #[Test]
    public function category_sums_in_per_event_mode_respect_date_range(): void
    {
        $this->primeDefaultCategories();

        $inRange = $this->makeEvent(1, '2026-06-10 19:00');
        $outOfRange = $this->makeEvent(2, '2025-06-10 19:00');

        $project = $this->makeProject(
            ['sold_tickets_mode' => 'per_event'],
            [$inRange, $outOfRange],
            [],
            [],
            [
                $this->makeCategoryValue(1, 1, 80),
                $this->makeCategoryValue(1, 2, 500),
                // Gesamtwert zählt im PER_EVENT-Modus nicht:
                $this->makeCategoryValue(1, null, 999),
            ]
        );

        $sums = $this->service->categorySums(
            $project,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        self::assertSame(['full' => 80, 'reduced' => null, 'free' => null], $sums);
    }

    #[Test]
    public function sold_tickets_fall_back_to_paid_categories_but_direct_value_wins(): void
    {
        $this->primeDefaultCategories();

        $fallback = $this->makeProject(
            ['sold_tickets_mode' => 'total', 'sold_tickets_total' => null],
            [],
            [],
            [],
            [
                $this->makeCategoryValue(1, null, 100),
                $this->makeCategoryValue(2, null, 40),
                $this->makeCategoryValue(3, null, 25),
            ]
        );
        // full + reduced, ohne Freikarten
        self::assertSame(140, $this->service->soldTickets($fallback));

        $directWins = $this->makeProject(
            ['sold_tickets_mode' => 'total', 'sold_tickets_total' => 150],
            [],
            [],
            [],
            [$this->makeCategoryValue(1, null, 100)]
        );
        self::assertSame(150, $this->service->soldTickets($directWins));
    }

    #[Test]
    public function quota_kpis_are_computed_from_category_sums(): void
    {
        $this->primeDefaultCategories();

        $project = $this->makeProject(
            ['sold_tickets_mode' => 'total', 'visitor_mode' => 'total', 'visitors_total' => 150],
            [],
            [],
            [],
            [
                $this->makeCategoryValue(1, null, 120),
                $this->makeCategoryValue(2, null, 40),
                $this->makeCategoryValue(3, null, 40),
            ]
        );

        // 40 Freikarten von 200 ausgegebenen = 20 %
        self::assertSame(20.0, $this->service->freeTicketsRate($project));
        // 40 ermäßigt von 160 verkauften = 25 %
        self::assertSame(25.0, $this->service->reducedTicketsRate($project));
        // 160 zahlend von 200 = 80 %
        self::assertSame(80.0, $this->service->payingRate($project));
        // 150 Besucher*innen bei 200 Karten = 25 % No-Show
        self::assertSame(25.0, $this->service->noShowRate(150, 200));
    }

    #[Test]
    public function quota_kpis_are_null_without_recorded_basis(): void
    {
        $this->primeDefaultCategories();

        $onlyPaid = $this->makeProject(
            ['sold_tickets_mode' => 'total'],
            [],
            [],
            [],
            [$this->makeCategoryValue(1, null, 100)]
        );

        // Keine Freikarten erfasst → Quote unbekannt, nicht 0
        self::assertNull($this->service->freeTicketsRate($onlyPaid));
        self::assertNull($this->service->reducedTicketsRate($onlyPaid));
        self::assertNull($this->service->noShowRate(null, 100));
        self::assertNull($this->service->noShowRate(100, null));
    }

    #[Test]
    public function visitors_estimate_prefers_tickets_issued_over_sold_tickets(): void
    {
        $this->primeDefaultCategories();

        $project = $this->makeProject(
            [
                'visitor_mode' => 'total',
                'visitors_total' => null,
                'sold_tickets_mode' => 'total',
                'sold_tickets_total' => 140,
            ],
            [],
            [],
            [],
            [
                $this->makeCategoryValue(1, null, 140),
                $this->makeCategoryValue(3, null, 30),
            ]
        );

        $result = $this->service->visitorsWithEstimate($project);

        // 170 ausgegebene Karten (inkl. Freikarten) statt 140 verkaufte
        self::assertSame(170, $result['value']);
        self::assertTrue($result['estimated']);
    }

    #[Test]
    public function for_scope_plan_reads_the_plan_data_set(): void
    {
        $project = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => 800,
        ]);
        $this->withPlanData($project, [
            'visitor_mode' => 'total',
            'visitors_total' => 1000,
        ]);

        self::assertSame(800, $this->service->visitors($project));
        self::assertSame(1000, $this->service->forScope('plan')->visitors($project));
        // forScope liefert einen Klon — die Ist-Instanz bleibt unverändert
        self::assertSame(800, $this->service->visitors($project));
    }

    #[Test]
    public function plan_comparison_computes_diff_and_attainment(): void
    {
        $project = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => 800,
            'revenue_mode' => 'total',
            'revenue_total' => 9000,
        ]);
        $this->withPlanData($project, [
            'visitor_mode' => 'total',
            'visitors_total' => 1000,
            'revenue_mode' => 'total',
            'revenue_total' => 10000,
        ]);

        $comparison = $this->service->planComparison($project);

        self::assertTrue($comparison['has_plan']);
        self::assertSame(1000, $comparison['metrics']['visitors']['plan']);
        self::assertSame(800, $comparison['metrics']['visitors']['actual']);
        self::assertSame(-200.0, $comparison['metrics']['visitors']['diff']);
        self::assertSame(80.0, $comparison['metrics']['visitors']['attainment']);
        self::assertSame(90.0, $comparison['metrics']['revenue']['attainment']);
        // Tickets ohne Plan → alles null
        self::assertNull($comparison['metrics']['sold_tickets']['plan']);
        self::assertNull($comparison['metrics']['sold_tickets']['attainment']);
    }

    #[Test]
    public function plan_comparison_reports_no_plan_without_plan_values(): void
    {
        $project = $this->makeProject([
            'visitor_mode' => 'total',
            'visitors_total' => 800,
        ]);

        $comparison = $this->service->planComparison($project);

        self::assertFalse($comparison['has_plan']);
    }

    #[Test]
    public function summary_returns_consistent_null_semantics_without_bi_data(): void
    {
        $project = $this->makeProject(null);

        $summary = $this->service->summary($project);

        self::assertNull($summary['visitors']);
        self::assertFalse($summary['visitors_estimated']);
        self::assertNull($summary['sold_tickets']);
        self::assertNull($summary['revenue']);
        self::assertNull($summary['avg_price']);
        self::assertSame(0, $summary['capacity']);
        self::assertNull($summary['occupancy']);
        self::assertSame(0, $summary['performances']);
        self::assertSame(0, $summary['event_days']);
    }
}

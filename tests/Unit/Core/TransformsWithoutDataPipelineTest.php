<?php

namespace Tests\Unit\Core;

use Artwork\Core\Dto\TransformsWithoutDataPipeline;
use Artwork\Modules\Calendar\DTO\CalendarFrontendDataDTO;
use Artwork\Modules\Calendar\DTO\CalendarRoomDTO;
use Artwork\Modules\Calendar\DTO\CalendarShiftDTO;
use Artwork\Modules\Calendar\DTO\EventDTO;
use Artwork\Modules\Calendar\DTO\MinimalCreatorDTO;
use Artwork\Modules\Calendar\DTO\ProjectDTO;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Transformation\TransformationContextFactory;
use Tests\TestCase;

/**
 * Der schnelle Serialisierungsweg (TransformsWithoutDataPipeline) muss exakt dieselbe Ausgabe liefern wie
 * die laravel-data-Pipeline — ein expliziter Transformationskontext erzwingt hier die Pipeline als Referenz.
 */
final class TransformsWithoutDataPipelineTest extends TestCase
{
    private function assertSameAsPipeline(Data $dto): void
    {
        $this->assertEquals($dto->transform(TransformationContextFactory::create()), $dto->transform());
        $this->assertSame(
            json_encode($dto->transform(TransformationContextFactory::create())),
            json_encode($dto->transform())
        );
        $this->assertSame(
            json_encode($dto->transform(TransformationContextFactory::create())),
            json_encode($dto)
        );
    }

    private function project(Lazy|array $leaders, ?array $group = null): ProjectDTO
    {
        return new ProjectDTO(
            id: 7,
            name: 'Projekt',
            status: ['id' => 1, 'name' => 'Läuft', 'color' => '#fff'],
            artistNames: null,
            leaders: $leaders,
            color: '#123456',
            group: $group,
            userIds: [1, 2],
        );
    }

    private function event(MinimalCreatorDTO|null|Optional $creator, ?ProjectDTO $project): EventDTO
    {
        return new EventDTO(
            id: 1,
            start: '2026-10-01 10:00',
            end: '2026-10-01 12:00',
            eventName: 'Probe',
            description: null,
            project: $project,
            eventType: ['id' => 3, 'name' => 'Probe', 'abbreviation' => 'P', 'hex_code' => '#000'],
            shifts: null,
            allDay: false,
            roomId: 4,
            roomName: 'Saal',
            startHour: 10,
            minutesFormStartHourToStart: 0,
            eventLengthInHours: 2,
            created_by: $creator,
            is_series: false,
            series_id: null,
            eventProperties: [['id' => 1, 'name' => 'Laut', 'icon' => 'IconSpeakerphone']],
            occupancy_option: false,
            declinedRoomId: null,
            eventStatus: null,
            subEvents: [],
            option_string: null,
        );
    }

    #[Test]
    public function event_with_nested_project_creator_and_group_matches_the_pipeline(): void
    {
        $group = $this->project([], null);
        $event = $this->event(
            new MinimalCreatorDTO(id: 5, first_name: 'Max', last_name: 'Muster', profile_photo_url: null),
            $this->project([['id' => 9, 'first_name' => 'Lea']], [$group])
        );

        $this->assertSameAsPipeline($event);
        $this->assertSame(['isMinimal', 'id'], array_slice(array_keys($event->transform()), 0, 2));
    }

    #[Test]
    public function optional_creator_is_left_out_and_inertia_lazy_is_resolved_like_the_pipeline(): void
    {
        $event = $this->event(Optional::create(), $this->project(Lazy::inertia(fn () => [['id' => 1]])));

        $this->assertSameAsPipeline($event);
        $this->assertArrayNotHasKey('created_by', $event->transform());
    }

    #[Test]
    public function room_tree_with_events_and_shifts_inside_arrays_matches_the_pipeline(): void
    {
        $shift = new CalendarShiftDTO(
            id: 2,
            startDate: '2026-10-01',
            endDate: '2026-10-01',
            start: '08:00',
            end: '14:00',
            break_minutes: 30,
            eventId: null,
            description: null,
            craftId: 1,
            shifts_qualifications: [],
            roomId: 4,
            assignedCounts: [3 => 2],
        );
        $room = new CalendarRoomDTO(
            roomId: 4,
            roomName: 'Saal',
            content: ['01.10.2026' => ['events' => [$this->event(null, null)], 'shifts' => [$shift]]],
        );
        $frontend = new CalendarFrontendDataDTO(rooms: [$room]);

        $this->assertSameAsPipeline($room);
        $this->assertSameAsPipeline($frontend);
    }

    #[Test]
    public function unsupported_values_fall_back_to_the_pipeline(): void
    {
        $withDate = new class (Carbon::parse('2026-10-01 10:00:00')) extends Data {
            use TransformsWithoutDataPipeline;

            public function __construct(public Carbon $at)
            {
            }
        };

        $this->assertSameAsPipeline($withDate);
        $this->assertSame('2026-10-01T10:00:00+02:00', $withDate->transform()['at']);
    }

    #[Test]
    public function partials_and_additional_data_still_go_through_the_pipeline(): void
    {
        $except = $this->event(null, null)->except('description');
        $this->assertArrayNotHasKey('description', $except->transform());
        $this->assertSameAsPipeline($except);

        $additional = $this->event(null, null)->additional(['extra' => 1]);
        $this->assertSame(1, $additional->transform()['extra']);
        $this->assertSameAsPipeline($additional);
    }

    #[Test]
    public function an_empty_data_context_left_by_the_pipeline_keeps_the_direct_path(): void
    {
        $event = $this->event(null, $this->project([]));
        $event->getDataContext();

        $this->assertIsArray($event->transform()['project']);
        $this->assertSameAsPipeline($event);
    }
}

<?php

namespace Tests\Feature\Projects\Plans;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\Accommodation\Models\AccommodationRoomType;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\SidebarTabComponent;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Feature\FeatureTestCase;
use ZipArchive;

/**
 * Veranstaltungsplan und Künstlerplan als Word: Projektzugriff, Sichtbarkeit von Team und
 * Künstleraufenthalten, Inhalte, ausgelassene leere Abschnitte und gespeicherte Terminarten.
 */
final class ProjectPlanExportTest extends FeatureTestCase
{
    private Project $project;

    private Room $church;

    private Room $restaurant;

    private EventType $concert;

    private EventType $transfer;

    private Event $concertEvent;

    private Event $transferEvent;

    private Event $rehearsalEvent;

    private ArtistResidency $residency;

    protected function setUp(): void
    {
        parent::setUp();

        // Vorhandene Platzierungen der geteilten Test-DB ausblenden (Rollback per Transaktion)
        DisclosureComponents::query()->delete();
        SidebarTabComponent::query()->delete();
        ComponentInTab::query()->delete();

        $this->church = Room::factory()->create([
            'name' => 'Pfarrkirche St. Peter und Paul',
            'street' => 'Bei der Peterskirche 9',
            'zip_code' => '02826',
            'city' => 'Görlitz',
        ]);
        $this->restaurant = Room::factory()->create([
            'name' => 'Restaurant Bürgerstübl',
            'street' => 'Neißstraße 27',
            'zip_code' => '02826',
            'city' => 'Görlitz',
        ]);

        $this->concert = EventType::factory()->create(['name' => 'Konzert']);
        $this->transfer = EventType::factory()->create(['name' => 'Transfer']);
        $rehearsal = EventType::factory()->create(['name' => 'Probe']);
        // Terminart ohne Termin im Projekt: taucht in der Auswahl nicht auf
        EventType::factory()->create(['name' => 'Ungenutzt']);

        $this->project = Project::factory()->create(['name' => 'Festival-Eröffnung', 'artists' => 'Nigel Short, Tenebrae Choir']);
        $this->project->forceFill(['linked_artists' => [['id' => 1, 'name' => 'Petr Štědroň'], ['id' => 2, 'name' => 'Nigel Short']]])
            ->save();

        $this->concertEvent = $this->event($this->concert, $this->church, '2026-08-25 19:30', '2026-08-25 21:30', [
            'eventName' => 'Festival-Konzert',
            'description' => "Grußworte\r\nKonzert & Preisübergabe",
        ]);
        $this->rehearsalEvent = $this->event($rehearsal, $this->church, '2026-08-24 10:00', '2026-08-24 13:00');
        $this->transferEvent = $this->event($this->transfer, $this->restaurant, '2026-08-26 09:00', '2026-08-26 10:20', [
            'eventName' => 'Shuttle nach Dresden',
        ]);

        $roomType = AccommodationRoomType::factory()->create(['name' => 'Einzelzimmer']);
        $hotel = Accommodation::factory()->create([
            'name' => 'Hotel Börse',
            'street' => 'Untermarkt 16',
            'zip_code' => '02826',
            'location' => 'Görlitz',
            'phone_number' => '+49 3581 7642 60',
        ]);
        $this->residency = ArtistResidency::factory()->create([
            'project_id' => $this->project->id,
            'name' => 'Petr Štědroň',
            'accommodation_id' => $hotel->id,
            'type_of_room' => $roomType->id,
            'arrival_date' => '2026-08-25',
            'arrival_time' => '16:30',
            'departure_date' => '2026-08-27',
            'days' => 2,
            'cost_per_night' => 987.65,
            'daily_allowance' => 43.21,
        ]);
    }

    #[Test]
    public function people_without_project_access_get_nothing(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('projects.plans.options', $this->project))->assertForbidden();
        $this->getJson(route('projects.plans.event-plan', $this->project))->assertForbidden();
        $this->getJson(route('projects.plans.artist-plan', $this->project))->assertForbidden();
    }

    #[Test]
    public function options_suggest_the_most_used_room_and_list_events_types_and_residencies(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['artist_plan_event_type_ids' => [$this->transfer->id, 999999]])->save();

        $response = $this->getJson(route('projects.plans.options', $this->project))->assertOk();

        $response->assertJsonPath('suggested_venue_room_id', $this->church->id)
            ->assertJsonPath('rooms.0.id', $this->church->id)
            ->assertJsonPath('rooms.0.address', 'Bei der Peterskirche 9, 02826 Görlitz')
            ->assertJsonPath('remembered_event_type_ids', [$this->transfer->id])
            ->assertJsonPath('can_see_artist_residencies', true)
            ->assertJsonPath('artist_residencies.0.id', $this->residency->id)
            ->assertJsonPath('artist_residencies.0.accommodation', 'Hotel Börse');

        $this->assertSame(['Konzert', 'Probe', 'Transfer'], collect($response->json('event_types'))->pluck('name')->all());
        $this->assertSame(
            [$this->rehearsalEvent->id, $this->concertEvent->id, $this->transferEvent->id],
            collect($response->json('events'))->pluck('id')->all()
        );
        $this->assertSame('Dienstag, 25. August 2026', $response->json('events.1.day'));
        $this->assertSame('19:30 – 21:30', $response->json('events.1.time'));
    }

    #[Test]
    public function event_plan_contains_venue_artists_schedule_and_team(): void
    {
        $this->actingAsAdmin();
        $role = ProjectRole::query()->create(['name' => 'KBB & Disposition']);
        $member = User::factory()->create([
            'first_name' => 'Gesa',
            'last_name' => 'Detlefsen',
            'phone_number' => '+49 151 26996867',
            'email' => 'gesa@example.test',
        ]);
        $this->project->users()->attach($member->id, ['roles' => [$role->id]]);

        $response = $this->get(route('projects.plans.event-plan', [
            'project' => $this->project,
            'venue_room_id' => $this->church->id,
        ]))->assertOk();
        $this->assertStringContainsString(
            'Veranstaltungsplan Festival-Eröffnung.docx',
            rawurldecode((string) $response->headers->get('content-disposition'))
        );
        $text = $this->documentText($response);

        foreach ([
            'VERANSTALTUNGSPLAN',
            '»Festival-Eröffnung«',
            'SPIELSTÄTTE',
            'Pfarrkirche St. Peter und Paul',
            'Bei der Peterskirche 9, 02826 Görlitz',
            'KÜNSTLER*INNEN',
            'Nigel Short',
            'Tenebrae Choir',
            'Petr Štědroň',
            'ZEITPLAN',
            'Montag, 24. August 2026',
            'Probe',
            'Festival-Konzert',
            'Grußworte',
            'Konzert & Preisübergabe',
            'Shuttle nach Dresden',
            'Restaurant Bürgerstübl',
            'PROJEKTTEAM',
            'Gesa Detlefsen',
            'KBB & Disposition',
            'Tel. +49 151 26996867',
            'gesa@example.test',
        ] as $expected) {
            $this->assertStringContainsString($expected, $text, $expected);
        }
        // Linked artist already in the free text is listed once only
        $this->assertSame(1, substr_count($text, 'Nigel Short'));
        // Event plan shows the room, not its address
        $this->assertStringNotContainsString('Neißstraße 27', $text);
    }

    #[Test]
    public function empty_sections_are_left_out(): void
    {
        $this->actingAsAdmin();
        $emptyProject = Project::factory()->create(['name' => 'Leeres Projekt', 'artists' => '']);

        $text = $this->documentText($this->get(route('projects.plans.event-plan', $emptyProject))->assertOk());

        $this->assertStringContainsString('»Leeres Projekt«', $text);
        foreach (['SPIELSTÄTTE', 'KÜNSTLER*INNEN', 'ZEITPLAN', 'PROJEKTTEAM'] as $heading) {
            $this->assertStringNotContainsString($heading, $text, $heading);
        }
    }

    #[Test]
    public function artist_plan_contains_selected_events_with_address_and_hotel_without_costs(): void
    {
        $user = $this->actingAsAdmin();

        $response = $this->get(route('projects.plans.artist-plan', [
            'project' => $this->project,
            'venue_room_id' => $this->church->id,
            'artist_residency_id' => $this->residency->id,
            'event_type_ids' => [$this->transfer->id],
            'event_ids' => [$this->transferEvent->id, $this->rehearsalEvent->id],
        ]))->assertOk();
        $this->assertStringContainsString(
            'Künstlerplan Petr Štědroň Festival-Eröffnung.docx',
            rawurldecode((string) $response->headers->get('content-disposition'))
        );
        $text = $this->documentText($response);

        foreach ([
            'KÜNSTLERPLAN',
            'für Petr Štědroň',
            'Bei der Peterskirche 9, 02826 Görlitz',
            'Mittwoch, 26. August 2026',
            '09:00 – 10:20',
            'Shuttle nach Dresden',
            'Restaurant Bürgerstübl, Neißstraße 27, 02826 Görlitz',
            'Probe',
            'UNTERKUNFT',
            'Hotel Börse',
            'Untermarkt 16, 02826 Görlitz',
            '+49 3581 7642 60',
            'Di., 25.08.2026, 16:30 Uhr',
            'Do., 27.08.2026',
            '2 Nächte',
            'Einzelzimmer',
        ] as $expected) {
            $this->assertStringContainsString($expected, $text, $expected);
        }
        $this->assertStringNotContainsString('Festival-Konzert', $text);
        $this->assertStringNotContainsString('987', $text);
        $this->assertStringNotContainsString('43,21', $text);
        $this->assertStringNotContainsString('43.21', $text);

        $this->assertSame([$this->transfer->id], $user->fresh()->artist_plan_event_type_ids);
    }

    #[Test]
    public function artist_plan_rejects_events_and_residencies_of_other_projects(): void
    {
        $this->actingAsAdmin();
        $otherProject = Project::factory()->create();
        $foreignEvent = $this->event($this->concert, $this->church, '2026-08-25 10:00', '2026-08-25 11:00', [
            'project_id' => $otherProject->id,
        ]);
        $foreignResidency = ArtistResidency::factory()->create(['project_id' => $otherProject->id]);

        $this->getJson(route('projects.plans.artist-plan', [
            'project' => $this->project,
            'event_ids' => [$foreignEvent->id],
        ]))->assertJsonValidationErrors('event_ids.0');
        $this->getJson(route('projects.plans.artist-plan', [
            'project' => $this->project,
            'artist_residency_id' => $foreignResidency->id,
        ]))->assertJsonValidationErrors('artist_residency_id');
    }

    #[Test]
    public function planning_events_need_the_planning_calendar_permission(): void
    {
        $planning = $this->event($this->concert, $this->church, '2026-08-23 18:00', '2026-08-23 19:00', [
            'eventName' => 'Geheime Vorplanung',
            'is_planning' => true,
        ]);
        $member = User::factory()->create();
        $this->project->users()->attach($member->id, ['can_write' => false]);
        $this->actingAs($member);

        $ids = collect($this->getJson(route('projects.plans.options', $this->project))->assertOk()->json('events'))
            ->pluck('id');
        $this->assertNotContains($planning->id, $ids);
        $this->assertContains($this->concertEvent->id, $ids);
        $text = $this->documentText($this->get(route('projects.plans.event-plan', $this->project))->assertOk());
        $this->assertStringNotContainsString('Geheime Vorplanung', $text);

        $this->actingAsUserWith(PermissionEnum::CAN_SEE_PLANNING_CALENDAR->value, $member);
        $text = $this->documentText($this->get(route('projects.plans.event-plan', $this->project))->assertOk());
        $this->assertStringContainsString('Geheime Vorplanung', $text);
    }

    #[Test]
    public function team_and_residencies_need_their_components_to_be_visible(): void
    {
        $member = User::factory()->create(['first_name' => 'Nur', 'last_name' => 'Lesend']);
        $this->project->users()->attach($member->id, ['can_write' => false]);
        $this->actingAs($member);

        $this->getJson(route('projects.plans.options', $this->project))
            ->assertOk()
            ->assertJsonPath('can_see_artist_residencies', false)
            ->assertJsonPath('artist_residencies', []);
        $this->getJson(route('projects.plans.artist-plan', [
            'project' => $this->project,
            'artist_residency_id' => $this->residency->id,
        ]))->assertForbidden();

        $text = $this->documentText($this->get(route('projects.plans.event-plan', $this->project))->assertOk());
        $this->assertStringNotContainsString('PROJEKTTEAM', $text);
        $this->assertStringContainsString('ZEITPLAN', $text);
    }

    #[Test]
    public function visible_team_hides_private_contact_data_of_others(): void
    {
        $tab = ProjectTab::factory()->create(['visible_for_all' => true]);
        foreach ([ProjectTabComponentEnum::PROJECT_TEAM, ProjectTabComponentEnum::ARTIST_RESIDENCIES] as $type) {
            $component = Component::create([
                'name' => $type->value . ' ' . uniqid(),
                'type' => $type->value,
                'data' => [],
                'special' => true,
            ]);
            ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => 1]);
        }

        $private = User::factory()->create([
            'first_name' => 'Privat',
            'last_name' => 'Person',
            'phone_number' => '+49 170 0000001',
            'phone_private' => true,
            'email' => 'privat@example.test',
        ]);
        $member = User::factory()->create();
        $this->project->users()->attach($private->id);
        $this->project->users()->attach($member->id, ['can_write' => false]);
        $this->actingAs($member);

        $text = $this->documentText($this->get(route('projects.plans.artist-plan', [
            'project' => $this->project,
            'artist_residency_id' => $this->residency->id,
        ]))->assertOk());

        $this->assertStringContainsString('ANSPRECHPERSONEN', $text);
        $this->assertStringContainsString('Privat Person', $text);
        $this->assertStringContainsString('privat@example.test', $text);
        $this->assertStringNotContainsString('+49 170 0000001', $text);
        $this->assertStringContainsString('Hotel Börse', $text);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function event(EventType $type, Room $room, string $start, string $end, array $attributes = []): Event
    {
        return Event::factory()->create([
            'project_id' => $this->project->id,
            'event_type_id' => $type->id,
            'room_id' => $room->id,
            'eventName' => null,
            'description' => null,
            'start_time' => Carbon::parse($start),
            'end_time' => Carbon::parse($end),
            'allDay' => false,
            ...$attributes,
        ]);
    }

    /**
     * Sichtbarer Text des Word-Dokuments, Absätze durch Zeilenumbrüche getrennt.
     */
    private function documentText(TestResponse $response): string
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $path = $response->baseResponse->getFile()->getPathname();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertNotFalse(simplexml_load_string($xml), 'document.xml is not well-formed');

        return html_entity_decode(
            strip_tags(str_replace('</w:p>', "\n", $xml)),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );
    }
}

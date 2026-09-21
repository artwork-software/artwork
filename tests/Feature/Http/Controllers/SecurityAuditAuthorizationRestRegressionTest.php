<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Chat\Models\Chat;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmContactType;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\IndividualTimes\Models\IndividualTimeSeries;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Sicherheits-Audit 21.09.2026 – Restpunkte (NIEDRIG / "bewusst offen" der ersten Runde):
 * Projektsuche ohne Gruppen, Termin-Detailrouten, Individualzeit-Serie anzeigen, CRM-Kontakt-Lookups,
 * entfernte tote Routen/Klassen, abgelaufene Einladung, Präsenzstatus.
 */
final class SecurityAuditAuthorizationRestRegressionTest extends TestCase
{
    // ------------------------------------------------------------------
    // 1. projects.search.single: viewAny + schlankes DTO
    // ------------------------------------------------------------------

    #[Test]
    public function project_search_without_groups_returns_slim_dto_and_skips_groups(): void
    {
        Project::factory()->create(['name' => 'Restpunkt Einzelprojekt', 'is_group' => false]);
        Project::factory()->create(['name' => 'Restpunkt Gruppe', 'is_group' => true]);

        $this->actingAsUserWith([]);

        $response = $this->getJson(route('projects.search.single', ['query' => 'Restpunkt']))->assertOk();

        $names = array_column($response->json(), 'name');
        $this->assertContains('Restpunkt Einzelprojekt', $names);
        $this->assertNotContains('Restpunkt Gruppe', $names);

        $this->assertSame(
            ['id', 'name', 'first_event_date', 'last_event_date', 'artists'],
            array_keys($response->json()[0])
        );
    }

    // ------------------------------------------------------------------
    // 2. Termin-Detailrouten: EventPolicy::view
    // ------------------------------------------------------------------

    #[Test]
    public function event_detail_routes_require_project_visibility(): void
    {
        $event = Event::factory()->create();
        Timeline::factory()->create(['event_id' => $event->id]);

        $this->actingAsUserWith([]);
        $this->getJson(route('events.description', $event))->assertForbidden();
        $this->getJson(route('events.timelines', $event))->assertForbidden();
        $this->getJson(route('events.series.show', $event))->assertForbidden();

        $this->actingAsUserWith([PermissionEnum::PROJECT_VIEW]);
        $this->getJson(route('events.description', $event))->assertOk();
        $this->getJson(route('events.timelines', $event))->assertOk();
        $this->getJson(route('events.series.show', $event))->assertOk();
    }

    #[Test]
    public function event_detail_routes_are_open_for_events_without_project(): void
    {
        $event = Event::factory()->create(['project_id' => null]);

        $this->actingAsUserWith([]);
        $this->getJson(route('events.description', $event))->assertOk();
        $this->getJson(route('events.timelines', $event))->assertOk();
        $this->getJson(route('events.series.show', $event))->assertOk();
    }

    #[Test]
    public function project_team_members_may_read_event_details(): void
    {
        $event = Event::factory()->create();
        $member = User::factory()->create();
        $event->project->users()->attach($member->id, ['can_write' => false]);

        $this->actingAsUserWith([], $member);
        $this->getJson(route('events.description', $event))->assertOk();
    }

    // ------------------------------------------------------------------
    // 3. Individualzeit-Serie anzeigen
    // ------------------------------------------------------------------

    #[Test]
    public function individual_time_series_show_requires_own_subject_or_planning_right(): void
    {
        $owner = User::factory()->create();
        $series = $this->createSeriesFor($owner);

        $this->actingAsUserWith([]);
        $this->getJson(route('individual-time-series.show', $series))->assertForbidden();

        $this->actingAsUserWith([], $owner);
        $this->getJson(route('individual-time-series.show', $series))
            ->assertOk()
            ->assertJsonPath('data.uuid', $series->uuid);

        $this->actingAsUserWith([PermissionEnum::AVAILABILITY_MANAGEMENT]);
        $this->getJson(route('individual-time-series.show', $series))->assertOk();
    }

    // ------------------------------------------------------------------
    // 4. CRM-Kontakt-Lookups: Gate crm.contacts.lookup
    // ------------------------------------------------------------------

    #[Test]
    public function crm_lookups_are_forbidden_without_any_related_right(): void
    {
        $contact = $this->createCrmContact();

        $this->actingAsUserWith([]);
        $this->getJson(route('crm.contacts.search', ['search' => 'Lookup']))->assertForbidden();
        $this->getJson(route('crm.contacts.data', $contact))->assertForbidden();
        $this->getJson(route('crm.contacts.tooltip', $contact))->assertForbidden();
    }

    #[Test]
    public function crm_lookups_are_allowed_with_crm_or_feature_rights(): void
    {
        $contact = $this->createCrmContact();

        $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $this->getJson(route('crm.contacts.search', ['search' => 'Lookup']))
            ->assertOk()
            ->assertJsonFragment(['display_name' => 'Lookup Kontakt']);
        $this->getJson(route('crm.contacts.data', $contact))->assertOk();
        $this->getJson(route('crm.contacts.tooltip', $contact))->assertOk();

        foreach (
            [
                PermissionEnum::DOCUMENT_REQUEST_CREATE,
                PermissionEnum::CONTRACT_EDIT_UPLOAD,
                PermissionEnum::TEAM_UPDATE,
                PermissionEnum::ADD_EDIT_OWN_PROJECT,
            ] as $permission
        ) {
            $this->actingAsUserWith([$permission]);
            $this->getJson(route('crm.contacts.search', ['search' => 'Lookup']))
                ->assertOk();
        }
    }

    #[Test]
    public function crm_search_payload_stays_slim(): void
    {
        $contact = $this->createCrmContact();

        $this->actingAsUserWith([PermissionEnum::CRM_VIEW]);
        $row = collect($this->getJson(route('crm.contacts.search', ['search' => 'Lookup']))->json())
            ->firstWhere('id', $contact->id);

        $this->assertNotNull($row);
        $this->assertSame(['id', 'display_name', 'profile_photo_url', 'contact_type'], array_keys($row));
    }

    #[Test]
    public function project_team_write_access_grants_crm_lookup(): void
    {
        $this->createCrmContact();
        $project = Project::factory()->create();
        $writer = User::factory()->create();
        $project->users()->attach($writer->id, ['can_write' => true]);

        $this->actingAsUserWith([], $writer);
        $this->getJson(route('crm.contacts.search', ['search' => 'Lookup']))->assertOk();
    }

    #[Test]
    public function tooltip_is_allowed_for_contacts_in_a_visible_project_team_only(): void
    {
        $contact = $this->createCrmContact();
        $project = Project::factory()->create();
        $project->teamCrmContacts()->attach($contact->id, ['roles' => json_encode([])]);
        $member = User::factory()->create();
        $project->users()->attach($member->id, ['can_write' => false]);

        $this->actingAsUserWith([], $member);
        // Tooltip: Kontakt steht im Team eines sichtbaren Projekts → erlaubt …
        $this->getJson(route('crm.contacts.tooltip', $contact))->assertOk();
        // … Suche/Daten bleiben ohne Lookup-Recht gesperrt
        $this->getJson(route('crm.contacts.search', ['search' => 'Lookup']))->assertForbidden();
        $this->getJson(route('crm.contacts.data', $contact))->assertForbidden();

        // Kontakt außerhalb sichtbarer Teams → Tooltip gesperrt
        $foreignContact = $this->createCrmContact('Fremder Kontakt');
        $this->getJson(route('crm.contacts.tooltip', $foreignContact))->assertForbidden();
    }

    // ------------------------------------------------------------------
    // 5. Tote Routen / Klassen entfernt
    // ------------------------------------------------------------------

    #[Test]
    public function dead_routes_and_classes_are_gone(): void
    {
        foreach (
            [
                'artist.toggle-active',
                'service-provider.contact.delete',
                'service-provider.contact.update',
                'projects.group.delete',
            ] as $routeName
        ) {
            $this->assertFalse(Route::has($routeName), "Route {$routeName} sollte entfernt sein");
        }

        $this->assertTrue(Route::has('service-provider.contact.store'));
        // Dateiprüfung statt class_exists: die optimierte Composer-Classmap kennt die Klassen
        // bis zum nächsten dump-autoload noch und würde ein include auf die fehlende Datei versuchen.
        $this->assertFileDoesNotExist(base_path('artwork/Modules/Shift/Events/ShiftUpdated.php'));
        $this->assertFileDoesNotExist(base_path('artwork/Modules/Shift/Events/PushesShiftModification.php'));
        $this->assertFileDoesNotExist(base_path('artwork/Modules/ServiceProvider/Models/ServiceProviderContacts.php'));
    }

    // ------------------------------------------------------------------
    // 6. Abgelaufene Einladung: Hinweisseite statt 401
    // ------------------------------------------------------------------

    #[Test]
    public function expired_invitation_with_valid_token_shows_notice_page(): void
    {
        $token = Str::random(20);
        Invitation::factory()->expired()->create([
            'email' => 'expired@example.test',
            'token' => Hash::make($token),
            'permissions' => [],
            'roles' => [],
        ]);

        $this->get('/users/invitations/accept?' . http_build_query([
            'email' => 'expired@example.test',
            'token' => $token,
        ]))
            ->assertOk()
            ->assertInertia(
                static fn ($page) => $page
                    ->component('Users/InvitationExpired')
                    ->where('email', 'expired@example.test')
            );

        // Falscher Token bleibt 401 – auch bei abgelaufener Einladung (keine Enumeration über die Hinweisseite)
        $this->get('/users/invitations/accept?' . http_build_query([
            'email' => 'expired@example.test',
            'token' => 'wrong-token',
        ]))->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // 7. Präsenzstatus: eigener Status, gemeinsamer Chat oder Dienstplan-Sichtrecht
    // ------------------------------------------------------------------

    #[Test]
    public function user_status_is_limited_to_self_chat_partners_and_shift_plan_viewers(): void
    {
        $other = User::factory()->create();

        $self = $this->actingAsUserWith([]);
        $this->getJson(route('user-status.show', $self->id))->assertOk()->assertJsonStructure(['status']);
        $this->getJson(route('user-status.show', $other->id))->assertForbidden();

        $chat = Chat::query()->create(['name' => 'Direkt', 'is_group' => false, 'created_by' => $self->id]);
        $chat->users()->attach([$self->id, $other->id]);
        $this->getJson(route('user-status.show', $other->id))->assertOk();

        $this->actingAsUserWith([PermissionEnum::VIEW_SHIFT_PLAN]);
        $this->getJson(route('user-status.show', $other->id))->assertOk();
    }

    // ------------------------------------------------------------------
    // Helfer
    // ------------------------------------------------------------------

    private function createCrmContact(string $displayName = 'Lookup Kontakt'): CrmContact
    {
        $type = CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'rest-lookup'],
            ['name' => 'Lookup-Typ', 'is_system' => false, 'is_active' => true]
        );

        return CrmContact::query()->create([
            'crm_contact_type_id' => $type->id,
            'display_name' => $displayName,
            'is_active' => true,
        ]);
    }

    private function createSeriesFor(User $owner): IndividualTimeSeries
    {
        $series = IndividualTimeSeries::query()->create([
            'uuid' => (string) Str::uuid(),
            'title' => 'Restpunkt-Serie',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'frequency' => 'weekly',
            'interval' => 1,
            'weekdays' => [1],
            'created_by' => $owner->id,
        ]);
        IndividualTime::query()->create([
            'series_uuid' => $series->uuid,
            'timeable_type' => User::class,
            'timeable_id' => $owner->id,
            'title' => 'Restpunkt-Serie',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'full_day' => false,
            'working_time_minutes' => 180,
            'break_minutes' => 0,
        ]);

        return $series;
    }
}

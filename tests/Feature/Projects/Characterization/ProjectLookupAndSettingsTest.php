<?php

namespace Tests\Feature\Projects\Characterization;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Hilfs-/Lese-Endpunkte (Bearbeiten-Seite, Existenzfilter, Scout-Suche,
 * Räume mit Belegungszeitraum) und der globalen Projekt-Einstellungen inkl. Papierkorb-Leeren.
 */
final class ProjectLookupAndSettingsTest extends FeatureTestCase
{
    #[Test]
    public function write_team_member_can_open_edit_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);

        $this->get('/projects/' . $project->id . '/edit')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Projects/Edit', false)
                ->where('project.id', $project->id));
    }

    #[Test]
    public function read_only_team_member_is_redirected_back_from_edit_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => false]);

        $this->get('/projects/' . $project->id . '/edit')->assertRedirect();
    }

    #[Test]
    public function filter_existing_ids_drops_deleted_and_unknown_projects(): void
    {
        $this->actingAs(User::factory()->create());
        $alive = Project::factory()->create();
        $trashed = Project::factory()->create();
        $trashed->delete();

        $response = $this->postJson(route('project.filterExistingIds'), [
            'ids' => [$alive->id, (string) $alive->id, $trashed->id, 999999999, 0, 'abc'],
        ]);

        $response->assertOk();
        $this->assertSame([$alive->id], $response->json());
    }

    #[Test]
    public function filter_existing_ids_without_ids_returns_empty_list(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('project.filterExistingIds'))->assertOk()->assertExactJson([]);
    }

    #[Test]
    public function scout_search_without_term_returns_empty_list(): void
    {
        $this->actingAs(User::factory()->create());
        Project::factory()->create(['name' => 'Hamlet']);

        $this->postJson(route('project.scoutSearch'), ['project_search' => ''])
            ->assertOk()
            ->assertExactJson([]);
    }

    #[Test]
    public function guest_cannot_use_scout_search(): void
    {
        $this->postJson(route('project.scoutSearch'), ['project_search' => 'Hamlet'])->assertUnauthorized();
    }

    #[Test]
    public function rooms_with_event_periods_aggregates_events_and_shifts_per_room(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $mainStage = Room::factory()->create(['name' => 'Große Bühne']);
        $studio = Room::factory()->create(['name' => 'Studio']);
        Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $mainStage->id,
            'start_time' => '2026-05-02 10:00:00',
            'end_time' => '2026-05-02 12:00:00',
        ]);
        Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => $mainStage->id,
            'start_time' => '2026-05-04 18:00:00',
            'end_time' => '2026-05-04 22:30:00',
        ]);
        Shift::factory()->create([
            'event_id' => null,
            'project_id' => $project->id,
            'room_id' => $studio->id,
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-01',
            'start' => '09:00',
            'end' => '17:00',
        ]);
        Event::factory()->create(['room_id' => $studio->id, 'start_time' => '2026-01-01 08:00:00']);

        $response = $this->getJson(route('projects.rooms-with-event-periods', $project));

        $response->assertOk()->assertExactJson([
            [
                'id' => $studio->id,
                'name' => 'Studio',
                'start_date' => '2026-05-01',
                'start_time' => '09:00',
                'end_date' => '2026-05-01',
                'end_time' => '17:00',
            ],
            [
                'id' => $mainStage->id,
                'name' => 'Große Bühne',
                'start_date' => '2026-05-02',
                'start_time' => '10:00',
                'end_date' => '2026-05-04',
                'end_time' => '22:30',
            ],
        ]);
    }

    #[Test]
    public function rooms_with_event_periods_is_forbidden_for_outsiders_as_json(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();

        $this->getJson(route('projects.rooms-with-event-periods', $project))->assertForbidden();
    }

    #[Test]
    public function project_settings_update_persists_flags(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $this->patch(route('project_settings.update'), [
            'attributes' => true,
            'state' => false,
            'state_required' => false,
            'managers' => true,
            'cost_center' => false,
            'budget_deadline' => true,
            'show_artists' => true,
            'crm_contacts_in_team' => false,
        ])->assertRedirect();

        $settings = app(ProjectCreateSettings::class)->refresh();
        $this->assertTrue($settings->attributes);
        $this->assertFalse($settings->state);
        $this->assertTrue($settings->managers);
        $this->assertFalse($settings->cost_center);
        $this->assertTrue($settings->budget_deadline);
        $this->assertTrue($settings->show_artists);
        $this->assertFalse($settings->crm_contacts_in_team);
    }

    #[Test]
    public function project_settings_update_validates_required_flags(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_SETTINGS_UPDATE->value);

        $this->patchJson(route('project_settings.update'), ['attributes' => 'vielleicht'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attributes', 'state', 'managers', 'cost_center', 'budget_deadline']);
    }

    #[Test]
    public function force_delete_all_settings_purges_trashed_attributes_only(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::TRASH_ACCESS->value,
            PermissionEnum::PROJECT_SETTINGS_UPDATE->value,
        ]);
        $trashedGenre = Genre::factory()->create();
        $trashedGenre->delete();
        $activeCategory = Category::factory()->create();

        $this->delete(route('projects.settings.force.all'))->assertRedirect(route('projects.settings.trashed'));

        $this->assertDatabaseMissing('genres', ['id' => $trashedGenre->id]);
        $this->assertDatabaseHas('categories', ['id' => $activeCategory->id, 'deleted_at' => null]);
    }

    #[Test]
    public function force_delete_all_settings_requires_settings_permission_besides_trash_access(): void
    {
        $this->actingAsUserWith(PermissionEnum::TRASH_ACCESS->value);
        $trashedGenre = Genre::factory()->create();
        $trashedGenre->delete();

        $this->delete(route('projects.settings.force.all'))->assertForbidden();

        $this->assertSoftDeleted('genres', ['id' => $trashedGenre->id]);
    }
}

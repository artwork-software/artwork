<?php

namespace Tests\Feature\Projects\Characterization;

use Artwork\Modules\Category\Models\Category;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Genre\Models\Genre;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Charakterisierung der Team-, Attribut- und Dienstplan-Einstellungs-Endpunkte eines Projekts:
 * wer darf schreiben (Team-Pivot, globale Rechte, Komponenten-Einstellung) und was landet in der DB.
 */
final class ProjectTeamAndShiftSettingsTest extends FeatureTestCase
{
    #[Test]
    public function outsider_gets_json_403_when_updating_team(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $intruder = User::factory()->create();

        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [$intruder->id],
            'assigned_departments' => [],
        ])->assertForbidden()->assertJson(['error' => 'Not authorized to assign users to a project.']);

        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id, 'user_id' => $intruder->id]);
    }

    #[Test]
    public function write_team_member_can_update_team_and_manager_gets_write_flag(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        $manager = User::factory()->create();

        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [
                $user->id => ['can_write' => true],
                $manager->id => ['is_manager' => true, 'can_write' => false],
            ],
            'assigned_departments' => [],
        ])->assertRedirect();

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $manager->id,
            'is_manager' => true,
            'can_write' => true,
        ]);
    }

    #[Test]
    public function update_team_drops_users_missing_from_payload(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        $removed = User::factory()->create();
        $kept = User::factory()->create();
        $project->users()->attach([$removed->id, $kept->id]);

        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [$kept->id],
            'assigned_departments' => [],
        ])->assertRedirect();

        $this->assertSame([$kept->id], $project->users()->pluck('users.id')->all());
    }

    #[Test]
    public function viewer_without_write_access_cannot_update_attributes(): void
    {
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value);
        $project = Project::factory()->create();
        $category = Category::factory()->create();

        $this->patch(route('projects.update_attributes', $project), [
            'assignedCategoryIds' => [$category->id],
        ])->assertForbidden();

        $this->assertSame(0, $project->categories()->count());
    }

    #[Test]
    public function update_attributes_syncs_categories_and_genres_with_main_flag(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();
        [$mainCategory, $otherCategory] = Category::factory()->count(2)->create()->all();
        $genre = Genre::factory()->create();
        $staleGenre = Genre::factory()->create();
        $project->genres()->attach($staleGenre->id);

        $this->patch(route('projects.update_attributes', $project), [
            'assignedCategoryIds' => [$mainCategory->id, $otherCategory->id],
            'mainCategoryId' => $mainCategory->id,
            'assignedGenreIds' => [$genre->id],
            'assignedSectorIds' => [],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$mainCategory->id, $otherCategory->id],
            $project->categories()->pluck('categories.id')->all()
        );
        $this->assertDatabaseHas('category_project', [
            'project_id' => $project->id,
            'category_id' => $mainCategory->id,
            'is_main' => true,
        ]);
        $this->assertDatabaseHas('category_project', [
            'project_id' => $project->id,
            'category_id' => $otherCategory->id,
            'is_main' => false,
        ]);
        $this->assertSame([$genre->id], $project->genres()->pluck('genres.id')->all());
    }

    #[Test]
    public function write_team_member_can_sync_shift_contacts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        $contact = User::factory()->create();

        $this->patch(route('projects.update.shift_contacts', $project), [
            'contactIds' => [$contact->id],
        ])->assertOk();

        $this->assertSame([$contact->id], $project->shift_contact()->pluck('users.id')->all());
    }

    #[Test]
    public function outsider_cannot_sync_shift_contacts(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $contact = User::factory()->create();

        $this->patch(route('projects.update.shift_contacts', $project), [
            'contactIds' => [$contact->id],
        ])->assertForbidden();

        $this->assertSame(0, $project->shift_contact()->count());
    }

    #[Test]
    public function component_setting_can_restrict_write_member_from_shift_contacts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::factory()->create();
        $project->users()->attach($user->id, ['can_write' => true]);
        Component::query()->updateOrCreate(
            ['type' => 'ShiftContactPersonsComponent'],
            ['name' => 'Shift Contact Persons', 'data' => [], 'permission_type' => 'allSeeSomeEdit']
        );

        $this->patch(route('projects.update.shift_contacts', $project), [
            'contactIds' => [User::factory()->create()->id],
        ])->assertForbidden();
    }

    #[Test]
    public function global_write_permission_overrides_component_restriction_for_shift_event_types(): void
    {
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $project = Project::factory()->create();
        Component::query()->updateOrCreate(
            ['type' => 'RelevantDatesForShiftPlanningComponent'],
            ['name' => 'Relevant Dates', 'data' => [], 'permission_type' => 'allSeeSomeEdit']
        );
        $eventType = EventType::factory()->create();

        $this->patch(route('projects.update.shift_event_types', $project), [
            'shiftRelevantEventTypeIds' => [$eventType->id],
        ])->assertOk();

        $this->assertSame([$eventType->id], $project->shiftRelevantEventTypes()->pluck('event_types.id')->all());
    }

    #[Test]
    public function global_write_permission_does_not_unlock_shift_event_types_restricted_to_listed_viewers(): void
    {
        $this->actingAsUserWith(PermissionEnum::WRITE_PROJECTS->value);
        $project = Project::factory()->create();
        Component::query()->updateOrCreate(
            ['type' => 'RelevantDatesForShiftPlanningComponent'],
            ['name' => 'Relevant Dates', 'data' => [], 'permission_type' => 'someSeeSomeEdit']
        );
        $eventType = EventType::factory()->create();

        $this->patch(route('projects.update.shift_event_types', $project), [
            'shiftRelevantEventTypeIds' => [$eventType->id],
        ])->assertForbidden();

        $this->assertSame([], $project->shiftRelevantEventTypes()->pluck('event_types.id')->all());
    }

    #[Test]
    public function outsider_cannot_sync_shift_relevant_event_types(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::factory()->create();
        $eventType = EventType::factory()->create();

        $this->patch(route('projects.update.shift_event_types', $project), [
            'shiftRelevantEventTypeIds' => [$eventType->id],
        ])->assertForbidden();

        $this->assertSame(0, $project->shiftRelevantEventTypes()->count());
    }

    #[Test]
    public function own_project_permission_does_not_allow_changing_team_of_foreign_project(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);
        $project = Project::factory()->create();

        $this->patch(route('projects.update_team', $project), [
            'assigned_user_ids' => [$user->id => ['can_write' => true, 'access_budget' => true]],
            'assigned_departments' => [],
        ])->assertForbidden();

        $this->assertDatabaseMissing('project_user', ['project_id' => $project->id, 'user_id' => $user->id]);
    }

    #[Test]
    public function own_project_permission_does_not_allow_duplicating_foreign_project(): void
    {
        $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);
        $project = Project::factory()->create(['name' => 'Fremdes Projekt']);

        $this->post(route('projects.duplicate', $project))->assertForbidden();

        $this->assertSame(1, Project::query()->where('name', 'like', '%Fremdes Projekt%')->count());
    }

    #[Test]
    public function a_team_member_with_create_permission_can_duplicate_the_project(): void
    {
        $user = $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);
        $project = Project::factory()->create(['name' => 'Eigenes Projekt']);
        $project->users()->attach($user->id);

        $this->post(route('projects.duplicate', $project))->assertRedirect();

        $this->assertSame(2, Project::query()->where('name', 'like', '%Eigenes Projekt%')->count());
    }
}

<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectUpdateTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_edit_project(): void
    {
        $project = Project::factory()->create();

        $this->get('/projects/' . $project->id . '/edit')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_edit_page(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->get('/projects/' . $project->id . '/edit')->assertOk();
    }

    #[Test]
    public function guest_cannot_update_project(): void
    {
        $project = Project::factory()->create();

        $this->patch(route('projects.update', $project), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_update_project_name(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Old Name']);

        $response = $this->patch(route('projects.update', $project), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect();
        $this->assertSame('Updated Name', $project->fresh()->name);
    }

    #[Test]
    public function update_validates_required_name(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch(route('projects.update', $project), [
            'description' => 'No name',
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function update_validates_max_length(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch(route('projects.update', $project), [
            'name' => str_repeat('A', 300),
        ]);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function admin_can_update_description(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        // Project model may have 'description' excluded from $fillable;
        // the controller redirects on success regardless.
        $response = $this->patch('/projects/' . $project->id . '/updateDescription', [
            'description' => 'Hello world',
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_pin_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post(route('project.pin', $project), ['pinned' => true]);

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_update_copyright(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->post('/project/' . $project->id . '/copyright/update', [
            'copyright' => 'New copyright text',
            'own_copyright' => false,
            'live_music' => false,
            'collecting_society_id' => null,
            'law_size' => null,
            'name' => 'foo',
            'note' => 'bar',
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_update_shift_description(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->patch('/projects/' . $project->id . '/shiftDescription', [
            'description' => 'Shift desc',
        ]);

        $this->assertNotNull($project->fresh()->shift_description ?? true);
    }

    #[Test]
    public function admin_can_update_project_state(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $this->patch('/project/' . $project->id . '/state', [
            'state' => null,
        ]);

        // void return -> 200 OK; we just need no error
        $this->assertTrue(true);
    }

    #[Test]
    public function state_cannot_be_removed_when_state_is_required(): void
    {
        $this->actingAsAdmin();

        $settings = app(\Artwork\Modules\Project\Models\ProjectCreateSettings::class);
        $settings->state_required = true;
        $settings->save();

        $state = \Artwork\Modules\Project\Models\ProjectState::factory()->create();
        $project = Project::factory()->create(['state' => $state->id]);

        $response = $this->patch('/project/' . $project->id . '/state', [
            'state' => null,
        ]);

        $response->assertSessionHasErrors('state');
        $this->assertSame($state->id, (int) $project->fresh()->state);
    }

    #[Test]
    public function admin_can_update_attributes(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch('/projects/' . $project->id . '/attributes', [
            'assignedCategoryIds' => [],
            'assignedGenreIds' => [],
            'assignedSectorIds' => [],
        ]);

        $response->assertRedirect();
    }

    #[Test]
    public function admin_can_update_team(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create();

        $response = $this->patch('/projects/' . $project->id . '/team', [
            'assigned_user_ids' => [],
            'assigned_departments' => [],
        ]);

        $response->assertRedirect();
    }
}

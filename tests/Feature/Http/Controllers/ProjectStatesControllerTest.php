<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectState;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectStatesControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('state.store'), ['name' => 'X', 'color' => '#fff'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('state.store'), [
            'name' => 'Active',
            'color' => '#abcdef',
            'is_planning' => false,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_states', ['name' => 'Active']);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $state = ProjectState::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('state.update', $state), [
            'name' => 'New',
            'color' => '#222',
            'is_planning' => true,
        ]);

        $response->assertOk();
        $this->assertSame('New', $state->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $state = ProjectState::factory()->create();

        $response = $this->delete(route('state.delete', $state));

        $response->assertOk();
        $this->assertSoftDeleted('project_states', ['id' => $state->id]);
    }

    #[Test]
    public function admin_can_restore_trashed_state(): void
    {
        $this->actingAsAdmin();
        $state = ProjectState::factory()->create();
        $state->delete();

        $response = $this->patch(route('state.restore', $state->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('project_states', ['id' => $state->id, 'deleted_at' => null]);
    }

    #[Test]
    public function admin_can_force_delete(): void
    {
        $this->actingAsAdmin();
        $state = ProjectState::factory()->create();
        $state->delete();

        $response = $this->delete(route('state.force', $state->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('project_states', ['id' => $state->id]);
    }
}

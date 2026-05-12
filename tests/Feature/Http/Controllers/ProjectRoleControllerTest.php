<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectRole;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectRoleControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('project-roles.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('project-roles.index'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('project-roles.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('project-roles.store'), [
            'name' => 'Director',
            'color' => '#abcdef',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_roles', ['name' => 'Director']);
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $role = ProjectRole::factory()->create(['name' => 'Old']);

        $response = $this->patch(route('project-roles.update', $role), [
            'name' => 'New',
            'color' => '#222',
        ]);

        $response->assertOk();
        $this->assertSame('New', $role->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $role = ProjectRole::factory()->create();

        $response = $this->delete(route('project-roles.destroy', $role));

        $response->assertOk();
        $this->assertDatabaseMissing('project_roles', ['id' => $role->id]);
    }
}

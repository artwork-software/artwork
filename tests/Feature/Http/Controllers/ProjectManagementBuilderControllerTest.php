<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectManagementBuilderControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('project-management-builder.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('project-management-builder.index'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $component = Component::query()->forceCreate([
            'name' => 'X',
            'type' => 'TextField',
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
        ]);
        $this->post(route('project-management-builder.store', $component), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();
        $component = Component::query()->forceCreate([
            'name' => 'X',
            'type' => 'TextField',
            'data' => [],
            'special' => false,
            'sidebar_enabled' => true,
        ]);

        $response = $this->post(route('project-management-builder.store', $component), [
            'order' => 1,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_management_builders', ['component_id' => $component->id]);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $pmb = ProjectManagementBuilder::factory()->create();

        $response = $this->delete(route('project-management-builder.destroy', $pmb));

        $response->assertOk();
        $this->assertDatabaseMissing('project_management_builders', ['id' => $pmb->id]);
    }

    #[Test]
    public function admin_can_update_order(): void
    {
        $this->actingAsAdmin();
        $a = ProjectManagementBuilder::factory()->create(['order' => 1]);
        $b = ProjectManagementBuilder::factory()->create(['order' => 2]);

        $response = $this->patch(route('project-management-builder.update.order'), [
            'components' => [
                ['id' => $b->id, 'order' => 1],
                ['id' => $a->id, 'order' => 2],
            ],
        ]);

        $response->assertOk();
    }
}

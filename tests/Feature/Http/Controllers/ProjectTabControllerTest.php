<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectTab;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectTabControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('tab.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tab.index'))->assertOk();
    }

    #[Test]
    public function admin_can_view_list(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tab.list'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store_tab(): void
    {
        $this->post(route('tab.store'), [])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_tab(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('tab.store'), [
            'name' => 'My Tab',
            'visible_for_all' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('project_tabs', ['name' => 'My Tab']);
    }

    #[Test]
    public function admin_can_update_tab(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();

        $response = $this->patch(route('tab.update', $tab), [
            'name' => 'Updated',
            'visible_for_all' => true,
        ]);

        $response->assertOk();
        $this->assertSame('Updated', $tab->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy_tab(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();

        $response = $this->delete(route('tab.destroy', $tab));

        $response->assertOk();
        $this->assertDatabaseMissing('project_tabs', ['id' => $tab->id]);
    }

    #[Test]
    public function admin_can_set_tab_as_default(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create(['default' => false]);

        $response = $this->patch(route('tab.update.default', $tab));

        $response->assertOk();
        $this->assertTrue((bool) $tab->fresh()->default);
    }

    #[Test]
    public function admin_can_reorder_tabs(): void
    {
        $this->actingAsAdmin();
        $a = ProjectTab::factory()->create(['order' => 1]);
        $b = ProjectTab::factory()->create(['order' => 2]);

        $response = $this->post(route('tab.reorder'), [
            'components' => [
                ['id' => $b->id],
                ['id' => $a->id],
            ],
        ]);

        $response->assertOk();
    }
}

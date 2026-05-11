<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Models\ProjectTabSidebarTab;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ProjectTabSidebarTabControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_sidebar_tab(): void
    {
        $tab = ProjectTab::factory()->create();

        $this->post(route('tab.sidebar.store', $tab), ['name' => 'Sidebar'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_sidebar_tab(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();

        $response = $this->post(route('tab.sidebar.store', $tab), ['name' => 'Sidebar A']);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_tab_sidebar_tabs', [
            'project_tab_id' => $tab->id,
            'name' => 'Sidebar A',
        ]);
    }

    #[Test]
    public function admin_can_update_sidebar_tab(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();
        $sidebar = ProjectTabSidebarTab::create([
            'project_tab_id' => $tab->id,
            'name' => 'Old',
            'order' => 1,
        ]);

        $response = $this->patch(route('tab.sidebar.update', $sidebar), ['name' => 'Updated']);

        $response->assertRedirect();
        $this->assertDatabaseHas('project_tab_sidebar_tabs', ['id' => $sidebar->id, 'name' => 'Updated']);
    }

    #[Test]
    public function admin_can_destroy_sidebar_tab(): void
    {
        $this->actingAsAdmin();
        $tab = ProjectTab::factory()->create();
        $sidebar = ProjectTabSidebarTab::create([
            'project_tab_id' => $tab->id,
            'name' => 'X',
            'order' => 1,
        ]);

        $response = $this->delete(route('sidebar.tab.destroy', $sidebar));

        $response->assertRedirect();
        $this->assertDatabaseMissing('project_tab_sidebar_tabs', ['id' => $sidebar->id]);
    }
}

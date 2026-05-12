<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Project\Models\SidebarTabComponent;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class SidebarTabComponentControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_remove_sidebar_component(): void
    {
        $this->delete('/settings/sidebar/component/999999/remove')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_remove_sidebar_component(): void
    {
        $this->actingAsAdmin();
        // Use factory if exists, else create direct
        $component = new SidebarTabComponent();
        $component->project_tab_sidebar_id = 1;
        $component->component_id = 1;
        $component->order = 1;
        $component->save();

        $response = $this->delete(route('sidebar.component.remove', $component));

        $response->assertRedirect();
        $this->assertDatabaseMissing('sidebar_tab_components', ['id' => $component->id]);
    }
}

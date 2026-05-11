<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ChecklistTemplateControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_checklist_templates_index(): void
    {
        $this->get(route('checklist_templates.management'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_checklist_templates_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('checklist_templates.management'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_view_checklist_templates_create(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('checklist_templates.create'));

        $response->assertOk();
    }

    #[Test]
    public function admin_can_store_checklist_template(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->post(route('checklist_templates.store'), [
            'name' => 'My Template',
            'user_id' => $admin->id,
            'users' => [],
            'task_templates' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('checklist_templates', ['name' => 'My Template']);
    }

    #[Test]
    public function admin_can_destroy_checklist_template(): void
    {
        $admin = $this->actingAsAdmin();
        $template = ChecklistTemplate::create(['name' => 'X', 'user_id' => $admin->id]);

        $response = $this->delete(route('checklist_templates.destroy', $template));

        $response->assertRedirect();
        $this->assertDatabaseMissing('checklist_templates', ['id' => $template->id]);
    }
}

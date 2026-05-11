<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class TaskTemplateControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_create_page(): void
    {
        $this->get(route('task_templates.create'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_create_page(): void
    {
        $this->actingAsAdmin();

        $this->get(route('task_templates.create'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('task_templates.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store(): void
    {
        $this->actingAsAdmin();
        $tpl = ChecklistTemplate::factory()->create();

        $response = $this->post(route('task_templates.store'), [
            'name' => 'TaskName',
            'description' => 'A task',
            'checklist_template_id' => $tpl->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('task_templates', ['name' => 'TaskName']);
    }

    #[Test]
    public function admin_can_view_edit(): void
    {
        $this->actingAsAdmin();
        $tt = TaskTemplate::factory()->create();

        $this->get('/task_templates/' . $tt->id . '/edit')->assertOk();
    }

    #[Test]
    public function admin_can_update(): void
    {
        $this->actingAsAdmin();
        $tt = TaskTemplate::factory()->create(['name' => 'Old']);

        $response = $this->patch('/task_templates/' . $tt->id, [
            'name' => 'New',
            'description' => 'desc',
            'done' => false,
            'checklist_template_id' => $tt->checklist_template_id,
        ]);

        $response->assertRedirect();
        $this->assertSame('New', $tt->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy(): void
    {
        $this->actingAsAdmin();
        $tt = TaskTemplate::factory()->create();

        $response = $this->delete('/task_templates/' . $tt->id);

        $response->assertRedirect();
        $this->assertDatabaseMissing('task_templates', ['id' => $tt->id]);
    }
}

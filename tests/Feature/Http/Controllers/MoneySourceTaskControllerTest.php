<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceTask;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class MoneySourceTaskControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_store_task(): void
    {
        $this->post(route('money_source.task.add'), [
            'money_source' => 1,
            'name' => 'Foo',
            'deadline' => '2025-01-01',
        ])->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_task(): void
    {
        $this->actingAsAdmin();
        $source = MoneySource::factory()->create();

        $response = $this->post(route('money_source.task.add'), [
            'money_source' => $source->id,
            'name' => 'My Task',
            'description' => 'Desc',
            'deadline' => '2025-12-31',
        ]);

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', [
            'money_source_id' => $source->id,
            'name' => 'My Task',
        ]);
    }

    #[Test]
    public function admin_can_mark_task_done(): void
    {
        $this->actingAsAdmin();
        $task = MoneySourceTask::factory()->create(['done' => false]);

        $response = $this->patch(route('money_source.task.done', $task));

        $this->assertContains($response->status(), [200, 302]);
    }

    #[Test]
    public function admin_can_mark_task_undone(): void
    {
        $this->actingAsAdmin();
        $task = MoneySourceTask::factory()->create(['done' => true]);

        $response = $this->patch(route('money_source.task.undone', $task));

        $this->assertContains($response->status(), [200, 302]);
        $this->assertDatabaseHas('money_source_tasks', ['id' => $task->id, 'done' => 0]);
    }
}

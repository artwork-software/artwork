<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Task;
use App\Support\Services\HistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HistoryServiceTest extends TestCase
{


    public function testHistoryOfDeletedModel()
    {
        $this->actingAs($this->adminUser());
        $project = Project::factory()->create();
        $task = Task::factory()->create();

        Config::set('history.task.deleted', 'Some deletion Text with {swap}');
        $task->delete();

        /** @var ProjectHistory $history */
        $history = (new HistoryService())->modelUpdated($task, $project, ['{swap}' => 'Swappy']);

        $this->assertNotNull($history?->description);
        $this->assertEquals('Some deletion Text with Swappy', $history->description);
    }

    public function testHistoryOfCreatedModel()
    {
        $this->actingAs($this->adminUser());
        $project = Project::factory()->create();
        $task = Task::factory()->create();

        Config::set('history.task.created', 'Some creation Text with {swap}');

        /** @var ProjectHistory $history */
        $history = (new HistoryService())->modelUpdated($task, $project, ['{swap}' => 'Swappy']);

        $this->assertNotNull($history?->description);
        $this->assertEquals('Some creation Text with Swappy', $history->description);
    }

    public function testHistoryOfUpdatedPropertyAddedModel()
    {
        $this->actingAs($this->adminUser());
        $project = Project::factory()->create();
        $task = Task::factory()->create(['description' => null]);
        $task->wasRecentlyCreated = false;

        $task->fill(['description' => 'new Description']);

        Config::set('history.task.properties.description.added', 'Some added Text with {swap}, {old}, and {new}');

        $collectionOfHistory = (new HistoryService())->modelUpdated($task, $project, ['{swap}' => 'Swappy']);

        $this->assertCount(1, $collectionOfHistory);
        $this->assertNotNull($collectionOfHistory->first()?->description);
        $this->assertEquals('Some added Text with Swappy, , and new Description', $collectionOfHistory->first()->description);
    }

    public function testHistoryOfUpdatedPropertyDeletedModel()
    {
        $this->actingAs($this->adminUser());
        $project = Project::factory()->create();
        $task = Task::factory()->create(['description' => 'old Description']);
        $task->wasRecentlyCreated = false;

        $task->fill(['description' => null]);

        Config::set('history.task.properties.description.deleted', 'Some added Text with {swap}, {old}, and {new}');

        $collectionOfHistory = (new HistoryService())->modelUpdated($task, $project, ['{swap}' => 'Swappy']);

        $this->assertCount(1, $collectionOfHistory);
        $this->assertNotNull($collectionOfHistory->first()?->description);
        $this->assertEquals('Some added Text with Swappy, old Description, and ', $collectionOfHistory->first()->description);
    }

    public function testHistoryOfUpdatePropertyUpdatedModel()
    {
        $this->actingAs($this->adminUser());
        $project = Project::factory()->create();
        $task = Task::factory()->create(['description' => 'old Description']);
        $task->wasRecentlyCreated = false;

        $task->fill(['description' => 'new Description']);

        Config::set('history.task.properties.description.updated', 'Some added Text with {swap}, {old}, and {new}');

        $collectionOfHistory = (new HistoryService())->modelUpdated($task, $project, ['{swap}' => 'Swappy']);

        $this->assertCount(1, $collectionOfHistory);
        $this->assertNotNull($collectionOfHistory->first()?->description);
        $this->assertEquals('Some added Text with Swappy, old Description, and new Description', $collectionOfHistory->first()->description);
    }
}

<?php

namespace Tests\Unit\Pure\Resources\Task;

use Artwork\Modules\Task\Http\Resources\TaskDashboardResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TaskDashboardResourceTest extends TestCase
{
    #[Test]
    public function it_formats_deadline_and_isFuture_correctly_for_future_dates(): void
    {
        $future = Carbon::now()->addYears(5);

        $project = (object) ['id' => 11, 'name' => 'P'];
        $checklist = (object) ['name' => 'CL', 'project' => $project];

        // Build mockable model with task_users() call - using anonymous class
        $model = new class ($future, $checklist) {
            public int $id = 7;
            public string $name = 'Task A';
            public string $description = 'desc';
            public bool $done = false;
            public ?string $user_who_done = null;
            public $checklist;
            public $deadline;
            public $done_at = null;
            public function __construct($deadline, $checklist) {
                $this->deadline = $deadline;
                $this->checklist = $checklist;
            }
            public function task_users() {
                return new class () {
                    public function get(): array { return []; }
                };
            }
        };

        $array = (new TaskDashboardResource($model))->toArray(new Request());

        $this->assertSame(7, $array['id']);
        $this->assertSame('Task A', $array['name']);
        $this->assertSame('CL', $array['checklistName']);
        $this->assertSame('P', $array['projectName']);
        $this->assertSame(11, $array['projectId']);
        $this->assertSame('desc', $array['description']);
        $this->assertTrue($array['isDeadlineInFuture']);
        $this->assertNull($array['done_at']);
        $this->assertFalse($array['done']);
    }

    #[Test]
    public function deadline_is_null_when_no_deadline(): void
    {
        $model = new class () {
            public int $id = 1;
            public string $name = '';
            public string $description = '';
            public bool $done = false;
            public ?string $user_who_done = null;
            public ?object $checklist = null;
            public $deadline = null;
            public $done_at = null;
            public function task_users() {
                return new class () { public function get(): array { return []; } };
            }
        };

        $array = (new TaskDashboardResource($model))->toArray(new Request());

        $this->assertNull($array['deadline']);
        $this->assertNull($array['isDeadlineInFuture']);
    }

    #[Test]
    public function wrap_is_null(): void
    {
        $this->assertNull(TaskDashboardResource::$wrap);
    }
}

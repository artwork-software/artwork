<?php

namespace Tests\Unit\Task\Modules\Services;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Task\Models\Task;
use Artwork\Modules\Task\Repositories\TaskRepository;
use Artwork\Modules\Task\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\TestCase;
use Throwable;
use Exception;

class TaskServiceTest extends TestCase
{
    /* private readonly TaskRepository $taskRepositoryMock;
     private readonly Checklist $checklistMock;
     private readonly Task $taskMock;
     private readonly SupportCollection $supportCollectionMock;
     private readonly HasMany $hasManyMock;

     protected function setUp(): void
     {
         $this->taskRepositoryMock = $this
             ->getMockBuilder(TaskRepository::class)
             ->disableOriginalConstructor()
             ->onlyMethods(['save', 'deleteByModel', 'findByModel', 'syncWithDetach', 'findById'])
             ->getMock();

         $this->checklistMock = $this->getMockBuilder(Checklist::class)
             ->disableOriginalConstructor()
             ->getMock();

         $this->taskMock = $this->getMockBuilder(Task::class)
             ->disableOriginalConstructor()
             ->getMock();

         $this->supportCollectionMock = $this->getMockBuilder(SupportCollection::class)
             ->disableOriginalConstructor()
             ->getMock();

         $this->hasManyMock = $this->getMockBuilder(HasMany::class)
             ->disableOriginalConstructor()
             ->getMock();
     }

     public function getService(): TaskService
     {
         return new TaskService($this->taskRepositoryMock);
     }

     public function testCreateNewTaskObject(): void
     {
         $attributes = ['name' => 'Test Task'];
         $taskService = $this->getService();
         $task = $taskService->createNewTaskObject($attributes);

         self::assertInstanceOf(Task::class, $task);
         self::assertSame($attributes['name'], $task->name);
     }*/

    /*public function testCreateTaskByRequest(): void
    {
        $data = new SupportCollection([
            'name' => 'Test Task',
            'description' => 'Description',
            'deadline' => '2024-12-31',
            'users' => [1, 2, 3]
        ]);

        $tasksCollection = new Collection([$this->taskMock]);

        $this->hasManyMock->method('get')->willReturn($tasksCollection);
        $this->hasManyMock->method('max')->willReturn(0);

        $this->checklistMock->method('tasks')->willReturn($this->hasManyMock);

        $this->taskRepositoryMock->expects(self::once())
            ->method('save')
            ->willReturn($this->taskMock);

        $task = $this->getService()->createTaskByRequest($this->checklistMock, $data);

        self::assertInstanceOf(Task::class, $task);
    }

    public function testDeleteByChecklist(): void
    {
        $this->taskRepositoryMock->expects(self::once())
            ->method('deleteByModel')
            ->with($this->checklistMock);

        $this->getService()->deleteByChecklist($this->checklistMock);
    }

    public function testGetByChecklist(): void
    {
        $collection = new Collection([$this->taskMock]);

        $this->taskRepositoryMock->expects(self::once())
            ->method('findByModel')
            ->with($this->checklistMock)
            ->willReturn($collection);

        $result = $this->getService()->getByChecklist($this->checklistMock);

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(1, $result);
    }

    public function testSyncTaskUsersWithDetach(): void
    {
        $this->taskRepositoryMock->expects(self::once())
            ->method('syncWithDetach')
            ->with($this->taskMock->task_users(), [1, 2, 3]);

        $this->getService()->syncTaskUsersWithDetach($this->taskMock, [1, 2, 3]);
    }

    public function testDeleteAll(): void
    {
        $tasks = new Collection([$this->taskMock]);

        $this->taskMock->expects(self::once())
            ->method('delete');

        $this->getService()->deleteAll($tasks);
    }

    public function testRestoreAll(): void
    {
        $tasks = new Collection([$this->taskMock]);

        $this->taskMock->expects(self::once())
            ->method('restore');

        $this->getService()->restoreAll($tasks);
    }

    public function testDuplicateTasksByChecklist(): void
    {
        $newChecklistMock = $this->getMockBuilder(Checklist::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tasks = new Collection([$this->taskMock]);

        $this->taskMock->expects(self::once())
            ->method('replicate')
            ->willReturn($this->taskMock);

        $this->taskRepositoryMock->expects(self::once())
            ->method('findByModel')
            ->with($this->checklistMock)
            ->willReturn($tasks);

        $this->taskRepositoryMock->expects(self::once())
            ->method('save')
            ->with($this->taskMock);

        $this->getService()->duplicateTasksByChecklist($this->checklistMock, $newChecklistMock);
    }

    public function testReorderTasks(): void
    {
        $tasks = new SupportCollection([
            ['id' => 1, 'order' => 0],
            ['id' => 2, 'order' => 1],
        ]);

        $this->taskRepositoryMock->expects(self::exactly(2))
            ->method('findById')
            ->willReturn($this->taskMock);

        $this->taskRepositoryMock->expects(self::exactly(2))
            ->method('save')
            ->willReturn($this->taskMock);

        $task = $this->getService()->reorderTasks($tasks);

        self::assertInstanceOf(Task::class, $task);
    }

    public function testDoneOrUndoneTask(): void
    {
        $this->taskMock->done = false;
        $this->taskRepositoryMock->expects(self::once())
            ->method('save')
            ->willReturn($this->taskMock);

        $task = $this->getService()->doneOrUndoneTask($this->taskMock, 1);

        self::assertTrue($task->done);
    }

    public function testUpdateByRequest(): void
    {
        $data = new SupportCollection([
            'name' => 'Updated Task',
            'description' => 'Updated Description',
            'deadline' => '2024-12-31',
            'users' => [1, 2, 3]
        ]);

        $this->taskMock->name = 'Updated Task';
        $this->taskMock->description = 'Updated Description';
        $this->taskMock->deadline = Carbon::parse('2024-12-31')->endOfDay();
        $this->taskRepositoryMock->expects(self::once())
            ->method('save')
            ->willReturn($this->taskMock);

        $task = $this->getService()->updateByRequest($this->taskMock, $data);

        self::assertSame('Updated Task', $task->name);
        self::assertSame('Updated Description', $task->description);
    }*/
}

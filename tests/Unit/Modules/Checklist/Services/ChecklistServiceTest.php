<?php

namespace Tests\Unit\Modules\Checklist\Services;

use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\Checklist\Services\ChecklistService;
use Artwork\Modules\Task\Services\TaskService;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ChecklistServiceTest extends TestCase
{
    private ChecklistService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChecklistService::class);
    }

    #[Test]
    public function get_by_id_returns_checklist_when_present(): void
    {
        $checklist = Checklist::factory()->create();

        $result = $this->service->getById($checklist->id);

        $this->assertNotNull($result);
        $this->assertSame($checklist->id, $result->id);
    }

    #[Test]
    public function get_by_id_returns_null_for_missing(): void
    {
        $this->assertNull($this->service->getById(999999));
    }

    #[Test]
    public function create_new_checklist_creates_unsaved_model(): void
    {
        $checklist = $this->service->createNewChecklist(['name' => 'My checklist']);

        $this->assertInstanceOf(Checklist::class, $checklist);
        $this->assertFalse($checklist->exists);
        $this->assertSame('My checklist', $checklist->name);
    }

    #[Test]
    public function duplicate_creates_copy_with_appended_suffix(): void
    {
        $checklist = Checklist::factory()->create(['name' => 'Original']);

        $copy = $this->service->duplicate($checklist);

        $this->assertInstanceOf(Checklist::class, $copy);
        $this->assertSame('Original (copy)', $copy->name);
        $this->assertSame($checklist->project_id, $copy->project_id);
    }

    #[Test]
    public function get_private_checklists_returns_collection(): void
    {
        $user = User::factory()->create();

        $result = $this->service->getPrivateChecklists($user->id, 1);

        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function assign_users_by_id_syncs_users(): void
    {
        $checklist = Checklist::factory()->create(['project_id' => null]);
        $user = User::factory()->create();

        $this->service->assignUsersById($checklist, [$user->id]);

        $this->assertTrue($checklist->users()->where('user_id', $user->id)->exists());
    }

    #[Test]
    public function delete_removes_checklist(): void
    {
        $checklist = Checklist::factory()->create();
        $taskService = app(TaskService::class);

        $this->service->delete($checklist, $taskService);

        $this->assertSoftDeleted('checklists', ['id' => $checklist->id]);
    }
}

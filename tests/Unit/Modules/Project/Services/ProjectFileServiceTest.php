<?php

namespace Tests\Unit\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectFile;
use Artwork\Modules\Project\Services\ProjectFileService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectFileServiceTest extends TestCase
{
    private ProjectFileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProjectFileService::class);
    }

    #[Test]
    public function delete_all_soft_deletes_each_file(): void
    {
        $files = ProjectFile::factory()->count(3)->create();

        $this->service->deleteAll($files);

        foreach ($files as $file) {
            $this->assertSoftDeleted('project_files', ['id' => $file->id]);
        }
    }

    #[Test]
    public function restore_all_restores_soft_deleted_files(): void
    {
        $files = ProjectFile::factory()->count(2)->create();
        foreach ($files as $file) {
            $file->delete();
        }

        $this->service->restoreAll($files);

        foreach ($files as $file) {
            $this->assertDatabaseHas('project_files', ['id' => $file->id, 'deleted_at' => null]);
        }
    }

    #[Test]
    public function force_delete_all_removes_files_completely(): void
    {
        $files = ProjectFile::factory()->count(2)->create();

        $this->service->forceDeleteAll($files);

        foreach ($files as $file) {
            $this->assertDatabaseMissing('project_files', ['id' => $file->id]);
        }
    }

    #[Test]
    public function restore_single_file_returns_true(): void
    {
        $file = ProjectFile::factory()->create();
        $file->delete();

        $result = $this->service->restore($file);

        $this->assertTrue($result);
        $this->assertDatabaseHas('project_files', ['id' => $file->id, 'deleted_at' => null]);
    }

    #[Test]
    public function force_delete_single_removes_record(): void
    {
        $file = ProjectFile::factory()->create();

        $result = $this->service->forceDelete($file);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('project_files', ['id' => $file->id]);
    }
}

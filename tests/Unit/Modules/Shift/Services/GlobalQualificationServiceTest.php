<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\GlobalQualification;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GlobalQualificationServiceTest extends TestCase
{
    private GlobalQualificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GlobalQualificationService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        GlobalQualification::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertGreaterThanOrEqual(2, $result->count());
    }

    #[Test]
    public function create_persists_global_qualification(): void
    {
        $this->service->create([
            'name' => 'Pyrotechnik',
            'icon' => 'IconFire',
        ]);

        $this->assertDatabaseHas('global_qualifications', ['name' => 'Pyrotechnik']);
    }

    #[Test]
    public function update_changes_attributes(): void
    {
        $qualification = GlobalQualification::factory()->create(['name' => 'Old name']);

        $this->service->update($qualification, ['name' => 'New name']);

        $this->assertDatabaseHas('global_qualifications', [
            'id' => $qualification->id,
            'name' => 'New name',
        ]);
    }

    #[Test]
    public function delete_removes_qualification(): void
    {
        $qualification = GlobalQualification::factory()->create();

        $this->service->delete($qualification);

        $this->assertDatabaseMissing('global_qualifications', ['id' => $qualification->id]);
    }

    #[Test]
    public function delete_all_removes_every_qualification(): void
    {
        GlobalQualification::factory()->count(3)->create();

        $this->service->deleteAll();

        $this->assertSame(0, GlobalQualification::query()->count());
    }
}

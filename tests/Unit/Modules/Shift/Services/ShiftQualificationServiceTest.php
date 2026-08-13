<?php

namespace Tests\Unit\Modules\Shift\Services;

use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShiftQualificationServiceTest extends TestCase
{
    private ShiftQualificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShiftQualificationService::class);
    }

    #[Test]
    public function get_all_ordered_by_creation_date_returns_collection(): void
    {
        ShiftQualification::factory()->count(3)->create();

        $result = $this->service->getAllOrderedByPosition();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertGreaterThanOrEqual(3, $result->count());
    }

    #[Test]
    public function is_still_available_returns_true_when_qualification_available(): void
    {
        $qualification = ShiftQualification::factory()->create(['available' => true]);

        $this->assertTrue($this->service->isStillAvailable($qualification->id));
    }

    #[Test]
    public function is_still_available_returns_false_when_qualification_not_available(): void
    {
        $qualification = ShiftQualification::factory()->create(['available' => false]);

        $this->assertFalse($this->service->isStillAvailable($qualification->id));
    }

    #[Test]
    public function is_still_available_returns_false_for_missing_id(): void
    {
        $this->assertFalse($this->service->isStillAvailable(99999999));
    }

    #[Test]
    public function delete_removes_qualification(): void
    {
        $qualification = ShiftQualification::factory()->create();

        $result = $this->service->delete($qualification);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('shift_qualifications', ['id' => $qualification->id]);
    }
}

<?php

namespace Tests\Unit\Modules\Availability\Services;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Availability\Services\AvailabilityService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AvailabilityServiceTest extends TestCase
{
    private AvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AvailabilityService::class);
    }

    #[Test]
    public function delete_removes_availability(): void
    {
        $availability = Availability::factory()->create([
            'available_type' => User::class,
            'available_id' => User::factory(),
        ]);

        $this->service->delete($availability);

        $this->assertDatabaseMissing('availabilities', ['id' => $availability->id]);
    }
}

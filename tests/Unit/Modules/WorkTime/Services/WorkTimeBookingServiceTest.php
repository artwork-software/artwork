<?php

namespace Tests\Unit\Modules\WorkTime\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserWorkTime;
use Artwork\Modules\WorkTime\Services\WorkTimeBookingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkTimeBookingServiceTest extends TestCase
{
    private WorkTimeBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkTimeBookingService::class);
    }

    #[Test]
    public function refresh_work_time_activations_runs_without_error(): void
    {
        // Ensure the method runs without throwing when there is nothing to update.
        $this->service->refreshWorkTimeActivations();

        $this->assertTrue(true);
    }

    #[Test]
    public function refresh_work_time_activations_activates_currently_valid_entries(): void
    {
        $user = User::factory()->create();
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->refreshWorkTimeActivations();

        $this->assertDatabaseHas('user_work_times', [
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function refresh_work_time_activations_deactivates_expired_entries(): void
    {
        $user = User::factory()->create();
        UserWorkTime::query()->insert([
            'user_id' => $user->id,
            'valid_from' => now()->subYears(2),
            'valid_until' => now()->subYear(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->service->refreshWorkTimeActivations();

        $this->assertDatabaseHas('user_work_times', [
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }
}

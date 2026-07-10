<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\WorkingHourCacheService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WorkingHourCacheServiceTest extends TestCase
{
    private WorkingHourCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = app(WorkingHourCacheService::class);
    }

    #[Test]
    public function get_weekly_data_returns_null_initially(): void
    {
        $this->assertNull($this->service->getWeeklyData('user', 1, 2024, 20));
    }

    #[Test]
    public function set_and_get_weekly_data_round_trip(): void
    {
        $data = ['planned' => '40h 0m', 'difference' => '0h 0m', 'isMinus' => false, 'daily_target' => '40h 0m'];

        $this->service->setWeeklyData('user', 5, 2024, 20, $data);

        $this->assertSame($data, $this->service->getWeeklyData('user', 5, 2024, 20));
    }

    #[Test]
    public function forget_for_entity_invalidates_cached_weeks(): void
    {
        $this->service->setWeeklyData('user', 7, 2024, 10, ['a' => 1]);
        $this->service->setWeeklyData('user', 7, 2024, 11, ['a' => 2]);
        $this->service->setWeeklyData('user', 7, 2024, 12, ['a' => 3]);

        $this->service->forgetForEntity('user', 7);

        $this->assertNull($this->service->getWeeklyData('user', 7, 2024, 10));
        $this->assertNull($this->service->getWeeklyData('user', 7, 2024, 11));
        $this->assertNull($this->service->getWeeklyData('user', 7, 2024, 12));
    }

    #[Test]
    public function entity_type_returns_user_for_user_instance(): void
    {
        $user = new User();

        $this->assertSame('user', WorkingHourCacheService::entityType($user));
    }

    #[Test]
    public function entity_type_returns_freelancer_for_freelancer_instance(): void
    {
        $freelancer = new Freelancer();

        $this->assertSame('freelancer', WorkingHourCacheService::entityType($freelancer));
    }

    #[Test]
    public function entity_type_returns_service_provider_for_service_provider_instance(): void
    {
        $provider = new ServiceProvider();

        $this->assertSame('service_provider', WorkingHourCacheService::entityType($provider));
    }

    #[Test]
    public function setting_same_week_twice_keeps_the_latest_cached_value(): void
    {
        $this->service->setWeeklyData('user', 9, 2024, 14, ['v' => 1]);
        $this->service->setWeeklyData('user', 9, 2024, 14, ['v' => 2]);

        $this->assertSame(['v' => 2], $this->service->getWeeklyData('user', 9, 2024, 14));

        $this->service->forgetForEntity('user', 9);
        $this->assertNull($this->service->getWeeklyData('user', 9, 2024, 14));
    }

    #[Test]
    public function data_cached_after_an_invalidation_uses_the_current_version(): void
    {
        $this->service->setWeeklyData('user', 10, 2024, 14, ['v' => 1]);
        $this->service->forgetForEntity('user', 10);

        $this->service->setWeeklyData('user', 10, 2024, 14, ['v' => 2]);

        $this->assertSame(['v' => 2], $this->service->getWeeklyData('user', 10, 2024, 14));

        $this->service->forgetForEntity('user', 10);

        $this->assertNull($this->service->getWeeklyData('user', 10, 2024, 14));
    }
}

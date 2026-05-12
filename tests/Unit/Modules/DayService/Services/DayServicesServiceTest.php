<?php

namespace Tests\Unit\Modules\DayService\Services;

use Artwork\Modules\DayService\Models\DayService;
use Artwork\Modules\DayService\Services\DayServicesService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DayServicesServiceTest extends TestCase
{
    private DayServicesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DayServicesService::class);
    }

    #[Test]
    public function get_all_returns_collection_of_day_services(): void
    {
        DayService::factory()->count(3)->create();

        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function create_persists_day_service(): void
    {
        $this->service->create([
            'name' => 'Catering',
            'icon' => 'IconBowl',
            'hex_color' => '#abcdef',
        ]);

        $this->assertDatabaseHas('day_services', [
            'name' => 'Catering',
            'icon' => 'IconBowl',
            'hex_color' => '#abcdef',
        ]);
    }

    #[Test]
    public function update_modifies_day_service(): void
    {
        $dayService = DayService::factory()->create(['name' => 'Old']);

        $this->service->update($dayService, [
            'name' => 'New',
            'icon' => 'IconNew',
            'hex_color' => '#ffffff',
        ]);

        $this->assertDatabaseHas('day_services', [
            'id' => $dayService->id,
            'name' => 'New',
            'icon' => 'IconNew',
            'hex_color' => '#ffffff',
        ]);
    }

    #[Test]
    public function delete_removes_day_service_and_detaches_relations(): void
    {
        $dayService = DayService::factory()->create();
        $user = User::factory()->create();
        $dayService->users()->attach($user->id, ['date' => '2024-01-01']);

        $this->service->delete($dayService);

        $this->assertDatabaseMissing('day_services', ['id' => $dayService->id]);
        $this->assertDatabaseMissing('day_serviceables', [
            'day_service_id' => $dayService->id,
        ]);
    }

    #[Test]
    public function attach_day_serviceable_creates_pivot(): void
    {
        $dayService = DayService::factory()->create();
        $provider = ServiceProvider::factory()->create();

        $this->service->attachDayServiceable($dayService, $provider, '2024-05-15');

        $this->assertDatabaseHas('day_serviceables', [
            'day_service_id' => $dayService->id,
            'day_serviceable_id' => $provider->id,
            'day_serviceable_type' => ServiceProvider::class,
            'date' => '2024-05-15',
        ]);
    }

    #[Test]
    public function attach_day_serviceable_does_nothing_on_null_target(): void
    {
        $dayService = DayService::factory()->create();

        $this->service->attachDayServiceable($dayService, null, '2024-05-15');

        $this->assertDatabaseMissing('day_serviceables', [
            'day_service_id' => $dayService->id,
        ]);
    }

    #[Test]
    public function find_model_instance_returns_user(): void
    {
        $user = User::factory()->create();

        $instance = $this->service->findModelInstance('user', $user->id);

        $this->assertInstanceOf(User::class, $instance);
        $this->assertSame($user->id, $instance->id);
    }

    #[Test]
    public function find_model_instance_returns_freelancer(): void
    {
        $freelancer = Freelancer::factory()->create();

        $instance = $this->service->findModelInstance('freelancer', $freelancer->id);

        $this->assertInstanceOf(Freelancer::class, $instance);
    }

    #[Test]
    public function find_model_instance_returns_service_provider(): void
    {
        $provider = ServiceProvider::factory()->create();

        $instance = $this->service->findModelInstance('service_provider', $provider->id);

        $this->assertInstanceOf(ServiceProvider::class, $instance);
    }

    #[Test]
    public function find_model_instance_returns_null_for_unknown_type(): void
    {
        $instance = $this->service->findModelInstance('totally_unknown', 1);

        $this->assertNull($instance);
    }

    #[Test]
    public function find_model_instance_returns_null_when_not_found(): void
    {
        $instance = $this->service->findModelInstance('user', 999999);

        $this->assertNull($instance);
    }
}

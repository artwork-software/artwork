<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Models\UserContract;
use Artwork\Modules\User\Services\UserContractService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserContractServiceTest extends TestCase
{
    private UserContractService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserContractService::class);
    }

    #[Test]
    public function get_all_returns_collection(): void
    {
        UserContract::factory()->count(2)->create();

        $result = $this->service->getAll();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function create_persists_user_contract(): void
    {
        $contract = $this->service->create([
            'name' => 'Standard',
            'free_full_days_per_week' => 2,
            'free_half_days_per_week' => 0,
            'special_day_rule_active' => false,
            'compensation_period' => 4,
            'free_sundays_per_season' => 4,
            'days_off_first_26_weeks' => 0,
        ]);

        $this->assertInstanceOf(UserContract::class, $contract);
        $this->assertTrue($contract->exists);
        $this->assertDatabaseHas('user_contracts', ['name' => 'Standard']);
    }

    #[Test]
    public function update_modifies_user_contract(): void
    {
        $contract = UserContract::factory()->create(['name' => 'Old name']);

        $updated = $this->service->update($contract, ['name' => 'New name']);

        $this->assertSame('New name', $updated->name);
        $this->assertDatabaseHas('user_contracts', ['id' => $contract->id, 'name' => 'New name']);
    }

    #[Test]
    public function delete_removes_user_contract(): void
    {
        $contract = UserContract::factory()->create();

        $this->service->delete($contract);

        $this->assertDatabaseMissing('user_contracts', ['id' => $contract->id]);
    }
}

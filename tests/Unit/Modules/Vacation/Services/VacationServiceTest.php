<?php

namespace Tests\Unit\Modules\Vacation\Services;

use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VacationServiceTest extends TestCase
{
    private VacationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VacationService::class);
    }

    #[Test]
    public function find_vacations_by_user_id_returns_collection(): void
    {
        $user = User::factory()->create();
        Vacation::factory()->count(2)->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2025-06-10',
        ]);

        $result = $this->service->findVacationsByUserId($user->id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(2, $result->count());
    }

    #[Test]
    public function find_vacations_by_user_id_returns_empty_when_none_exist(): void
    {
        $user = User::factory()->create();

        $result = $this->service->findVacationsByUserId($user->id);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function delete_removes_vacation(): void
    {
        $user = User::factory()->create();
        $vacation = Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
        ]);

        $this->service->delete($vacation);

        $this->assertDatabaseMissing('vacations', ['id' => $vacation->id]);
    }

    #[Test]
    public function find_vacations_by_freelancer_id_returns_empty_collection(): void
    {
        $result = $this->service->findVacationsByFreelancerId(1);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    #[Test]
    public function find_vacation_within_interval_returns_matching_vacations(): void
    {
        $user = User::factory()->create();
        Vacation::factory()->create([
            'vacationer_type' => User::class,
            'vacationer_id' => $user->id,
            'date' => '2025-04-15',
        ]);

        $result = $this->service->findVacationWithinInterval($user, '2025-04-15');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }
}

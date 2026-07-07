<?php

namespace Tests\Unit\Modules\Vacation\Services;

use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class VacationConflictServiceTest extends TestCase
{
    private VacationConflictService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VacationConflictService::class);
    }

    #[Test]
    public function create_persists_vacation_conflict(): void
    {
        $existing = VacationConflict::factory()->create();

        $conflict = $this->service->create([
            'vacation_id' => $existing->vacation_id,
            'shift_id' => $existing->shift_id,
            'user_name' => 'Tester',
            'date' => '2025-04-01',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);

        $this->assertInstanceOf(VacationConflict::class, $conflict);
        $this->assertTrue($conflict->exists);
        $this->assertSame('Tester', $conflict->user_name);
    }

    #[Test]
    public function check_vacation_conflicts_on_day_returns_void_when_no_user_or_freelancer(): void
    {
        // Should not throw when neither user nor freelancer is provided
        $this->service->checkVacationConflictsOnDay(
            '2025-01-01',
            null,
            null,
            app(NotificationService::class)
        );

        $this->assertTrue(true);
    }
}

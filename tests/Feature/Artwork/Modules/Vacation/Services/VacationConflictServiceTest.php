<?php

namespace Tests\Feature\Artwork\Modules\Vacation\Services;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Vacation\Models\Vacation;
use Artwork\Modules\Vacation\Models\VacationConflict;
use Artwork\Modules\Vacation\Services\VacationConflictService;
use Tests\TestCase;

class VacationConflictServiceTest extends TestCase
{
    protected VacationConflictService $vacationConflictService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vacationConflictService = app(VacationConflictService::class);
    }

    public function testCreate(): void
    {
        $vacation = Vacation::factory()->create();
        $shift = Shift::factory()->create();
        $data = [
            'vacation_id' => $vacation->id,
            'shift_id' => $shift->id,
            'user_name' => 'Test User',
            'date' => '2022-01-01',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ];

        $conflict = $this->vacationConflictService->create($data);

        $this->assertInstanceOf(VacationConflict::class, $conflict);
        $this->assertEquals($data['vacation_id'], $conflict->vacation_id);
        $this->assertEquals($data['shift_id'], $conflict->shift_id);
        $this->assertEquals($data['user_name'], $conflict->user_name);
        $this->assertEquals($data['date'], $conflict->date);
        $this->assertEquals($data['start_time'], $conflict->start_time->format('H:i:s'));
        $this->assertEquals($data['end_time'], $conflict->end_time->format('H:i:s'));
    }
}

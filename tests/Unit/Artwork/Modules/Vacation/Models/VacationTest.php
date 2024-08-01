<?php

namespace Tests\Unit\Artwork\Modules\Vacation\Models;

use Artwork\Modules\Vacation\Models\Vacation;
use Tests\TestCase;

class VacationTest extends TestCase
{
    public function testGetDateCastedAttribute(): void
    {
        $vacation = Vacation::factory()->make([
            'date' => '2022-12-31',
        ]);

        $this->assertEquals('Sa., 31.12.2022', $vacation->date_casted);
    }

    public function testGetHasConflictsAttribute(): void
    {
        $vacation = Vacation::factory()->hasConflicts(1)->create();

        $this->assertTrue($vacation->has_conflicts);

        $vacation = Vacation::factory()->hasConflicts(0)->create();

        $this->assertFalse($vacation->has_conflicts);
    }
}

<?php

namespace Tests\Feature\Shifts;

use Carbon\Carbon;
use Tests\TestCase;

class ShiftPlanEventApiTest extends TestCase
{
    public function testShiftPlanAllEndpointIncludesShiftPresetsAndGroups(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user);

        $today = Carbon::now()->format('Y-m-d');

        $response = $this->getJson(route('shift.plan.all', [
            'start_date' => $today,
            'end_date' => $today,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'days',
            'shiftPlan',
            'singleShiftPresets',
            'shiftGroupPresets',
        ]);
    }
}

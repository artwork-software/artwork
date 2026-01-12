<?php

namespace Tests\Feature\ShiftPlan;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftUser;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;

class UpdateShortDescriptionTest extends TestCase
{
    public function testUpdateShortDescriptionRequiresEntityType(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $shift = Shift::factory()->create([
            'description' => 'Globale Schichtbeschreibung',
            'start' => '10:00',
            'end' => '12:00',
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ]);

        $worker = User::factory()->create();
        $qualification = ShiftQualification::query()->create([
            'icon' => 'briefcase-icon',
            'name' => 'Mitarbeiter',
            'available' => true,
        ]);

        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => $qualification->id,
            'shift_count' => 1,
            'craft_abbreviation' => null,
            'short_description' => null,
            'start_date' => null,
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,
        ]);

        $shift->load('users');
        $pivotId = $shift->users->firstWhere('id', $worker->id)->pivot->id;

        $response = $this->postJson(route('shifts.updateShortDescription'), [
            'shiftPivotId' => $pivotId,
            'short_description' => 'Meine individuelle Notiz',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['entity', 'entity.type']);
        $this->assertNull(ShiftUser::findOrFail($pivotId)->short_description);
    }

    public function testUpdateShortDescriptionUpdatesPivotAndDoesNotChangeShiftDescription(): void
    {
        $admin = $this->adminUser();
        $this->actingAs($admin);

        $shift = Shift::factory()->create([
            'description' => 'Globale Schichtbeschreibung',
            'start' => '10:00',
            'end' => '12:00',
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ]);

        $worker = User::factory()->create();
        $qualification = ShiftQualification::query()->create([
            'icon' => 'briefcase-icon',
            'name' => 'Mitarbeiter',
            'available' => true,
        ]);

        $shift->users()->attach($worker->id, [
            'shift_qualification_id' => $qualification->id,
            'shift_count' => 1,
            'craft_abbreviation' => null,
            'short_description' => null,
            'start_date' => null,
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,
        ]);

        $shift->load('users');
        $pivotId = $shift->users->firstWhere('id', $worker->id)->pivot->id;

        $response = $this->post(route('shifts.updateShortDescription'), [
            'shiftPivotId' => $pivotId,
            'entity' => ['type' => 'user'],
            'short_description' => 'Meine individuelle Notiz',
        ]);

        $response->assertOk();

        $this->assertSame('Meine individuelle Notiz', ShiftUser::findOrFail($pivotId)->short_description);
        $this->assertSame('Globale Schichtbeschreibung', $shift->fresh()->description);
    }
}

<?php

namespace Tests\Feature\Shifts;

use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ShiftMultiEditRemovalTest extends TestCase
{
    public function test_user_can_be_removed_from_shift_via_multi_edit()
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $shift = Shift::factory()->create();

        // Use factory with correct namespace if possible, or just create model
        $qualification = ShiftQualification::create([
            'name' => 'Test Qualification',
            'available' => true,
            'icon' => 'bell-icon',
        ]);

        // Assign user to shift manually
        $shiftWorker = ShiftWorker::create([
            'shift_id' => $shift->id,
            'employable_type' => User::class,
            'employable_id' => $user->id,
            'shift_qualification_id' => $qualification->id,
            'craft_abbreviation' => 'TEST',
            'start_date' => $shift->start_date ?? today(),
            'end_date' => $shift->end_date ?? today(),
            'start_time' => $shift->start ?? '08:00',
            'end_time' => $shift->end ?? '16:00',
        ]);

        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $user->id,
            'deleted_at' => null,
        ]);

        $payload = [
            'userType' => 0, // User
            'userTypeId' => $user->id,
            'craft_abbreviation' => 'TEST',
            'shiftsToHandle' => [
                'assignToShift' => [],
                'removeFromShift' => [$shift->id],
            ],
        ];

        $response = $this->actingAs($admin)->postJson(route('shift.multi.edit.save'), $payload);

        $response->assertSuccessful();

        // Check if it's permanently deleted
        $this->assertDatabaseMissing('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $user->id,
        ]);

        // Also check if it's removed from the shift's users relationship
        $this->assertCount(0, $shift->fresh()->users);
    }
}

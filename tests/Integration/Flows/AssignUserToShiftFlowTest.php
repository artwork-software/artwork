<?php

namespace Tests\Integration\Flows;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsDomainData;
use Tests\Feature\FeatureTestCase;

/**
 * End-to-end flow: Build qualified user, create event & shift, assign user
 * through HTTP shift.assignUserByType route, verify Shift::users() relation.
 */
final class AssignUserToShiftFlowTest extends FeatureTestCase
{
    use BuildsDomainData;

    #[Test]
    public function admin_can_assign_qualified_user_to_shift_via_http(): void
    {
        $this->actingAsAdmin();

        // 1. User with qualification
        $user = $this->createUserWithQualifications(['Sound']);
        $qualification = ShiftQualification::query()->where('name', 'Sound')->firstOrFail();

        // 2. Craft → Event → Shift (factories + relations, since shift.store has
        //    side-effects and direct route is more reliable for setup)
        $craft = Craft::factory()->create();
        $event = Event::factory()->create();
        $shift = Shift::factory()->create([
            'event_id' => $event->id,
            'craft_id' => $craft->id,
        ]);

        // 3. HTTP assignment
        $response = $this->post(route('shift.assignUserByType', $shift), [
            'userType' => 0, // 0 = user
            'userId' => $user->id,
            'shiftQualificationId' => $qualification->id,
            'craft_abbreviation' => 'S',
        ]);

        $response->assertSuccessful();

        // 4. DB assertion: polymorphic shift_workers pivot contains the user
        $this->assertDatabaseHas('shift_workers', [
            'shift_id' => $shift->id,
            'employable_id' => $user->id,
            'employable_type' => User::class,
        ]);

        $this->assertTrue(
            $shift->fresh('users')->users->pluck('id')->contains($user->id),
            'Shift::users() should include the assigned user'
        );
    }

    #[Test]
    public function assigning_invalid_user_type_does_not_attach_anyone(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $this->postJson(route('shift.assignUserByType', $shift), [
            'userType' => 99,
            'userId' => 1,
        ])->assertUnprocessable();

        $this->assertSame(0, $shift->fresh('users')->users->count());
    }

    #[Test]
    public function assigning_nonexistent_user_id_does_not_attach(): void
    {
        $this->actingAsAdmin();
        $craft = Craft::factory()->create();
        $shift = Shift::factory()->create(['craft_id' => $craft->id]);
        $qualification = ShiftQualification::factory()->create(['name' => 'Generic']);

        $response = $this->post(route('shift.assignUserByType', $shift), [
            'userType' => 0,
            'userId' => PHP_INT_MAX,
            'shiftQualificationId' => $qualification->id,
            'craft_abbreviation' => 'G',
        ]);

        $response->assertSuccessful();
        $this->assertSame(0, $shift->fresh('users')->users->count());
    }

    #[Test]
    public function assigning_multiple_qualified_users_attaches_each(): void
    {
        $this->actingAsAdmin();
        $qualification = ShiftQualification::factory()->create(['name' => 'Light']);
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u1->shiftQualifications()->attach($qualification);
        $u2->shiftQualifications()->attach($qualification);

        $craft = Craft::factory()->create();
        $shift = Shift::factory()->create(['craft_id' => $craft->id]);

        foreach ([$u1, $u2] as $u) {
            $this->post(route('shift.assignUserByType', $shift), [
                'userType' => 0,
                'userId' => $u->id,
                'shiftQualificationId' => $qualification->id,
                'craft_abbreviation' => 'L',
            ])->assertSuccessful();
        }

        $shiftUserIds = $shift->fresh('users')->users->pluck('id');
        $this->assertTrue($shiftUserIds->contains($u1->id));
        $this->assertTrue($shiftUserIds->contains($u2->id));
    }
}

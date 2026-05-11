<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftDeleteTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_destroy_shift(): void
    {
        $shift = Shift::factory()->create();

        $this->deleteJson(route('shifts.destroy', $shift))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_destroy_non_committed_shift(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create(['is_committed' => false]);

        $response = $this->deleteJson(route('shifts.destroy', $shift));

        $response->assertSuccessful();
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function destroy_shift_returns_404_for_unknown_shift(): void
    {
        $this->actingAsAdmin();

        $response = $this->deleteJson(route('shifts.destroy', ['shift' => PHP_INT_MAX]));

        $response->assertNotFound();
    }

    #[Test]
    public function guest_cannot_bulk_delete_shifts(): void
    {
        $this->postJson(route('shifts.multi.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_bulk_delete_shifts(): void
    {
        $this->actingAsAdmin();
        $shiftA = Shift::factory()->create(['is_committed' => false]);
        $shiftB = Shift::factory()->create(['is_committed' => false]);

        $response = $this->postJson(route('shifts.multi.delete'), [
            'shift_ids' => [$shiftA->id, $shiftB->id],
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseMissing('shifts', ['id' => $shiftA->id]);
        $this->assertDatabaseMissing('shifts', ['id' => $shiftB->id]);
    }

    #[Test]
    public function admin_bulk_delete_with_unknown_ids_is_silent_noop(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shifts.multi.delete'), [
            'shift_ids' => [PHP_INT_MAX, PHP_INT_MAX - 1],
        ]);

        $response->assertSuccessful();
    }

    #[Test]
    public function admin_bulk_delete_with_empty_payload_is_noop(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $response = $this->postJson(route('shifts.multi.delete'), ['shift_ids' => []]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('shifts', ['id' => $shift->id]);
    }

    #[Test]
    public function guest_cannot_remove_all_shift_users(): void
    {
        $shift = Shift::factory()->create();

        $this->deleteJson(route('shift.removeAllUsers', $shift))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_remove_all_shift_users(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $response = $this->delete(route('shift.removeAllUsers', $shift));

        $response->assertRedirect();
    }

    #[Test]
    public function guest_cannot_remove_user_from_shift(): void
    {
        $this->deleteJson(route('shift.removeUserByType', ['usersPivotId' => 1, 'userType' => 0]))
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_remove_unknown_pivot_returns_noop_for_known_user_type(): void
    {
        $this->actingAsAdmin();

        $response = $this->delete(route('shift.removeUserByType', [
            'usersPivotId' => PHP_INT_MAX,
            'userType' => 0,
        ]));

        // Pivot lookup either throws or returns null -> controller returns null (200)
        $response->assertSuccessful();
    }

    #[Test]
    public function admin_remove_with_unknown_user_type_is_noop(): void
    {
        $this->actingAsAdmin();

        $response = $this->delete(route('shift.removeUserByType', [
            'usersPivotId' => 1,
            'userType' => 99,
        ]));

        $response->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_delete_calendar_cell(): void
    {
        $this->postJson(route('multi-edit.calendar.cell.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_delete_calendar_cell_with_empty_entities(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('multi-edit.calendar.cell.delete'), [
            'entities' => [],
        ]);

        $response->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_delete_multi_edit_cell(): void
    {
        $this->postJson(route('multi-edit.cell.delete'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_delete_multi_edit_cell_with_empty_entities_is_noop(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('multi-edit.cell.delete'), [
            'entities' => [],
        ]);

        $response->assertSuccessful();
    }
}

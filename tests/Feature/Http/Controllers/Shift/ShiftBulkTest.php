<?php

namespace Tests\Feature\Http\Controllers\Shift;

use Artwork\Modules\Shift\Models\Shift;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class ShiftBulkTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_bulk_duplicate_shifts(): void
    {
        $this->postJson(route('shifts.multi.duplicate'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_can_bulk_duplicate_shifts(): void
    {
        $this->actingAsAdmin();
        $shift = Shift::factory()->create();

        $response = $this->postJson(route('shifts.multi.duplicate'), [
            'shift_ids' => [$shift->id],
        ]);

        $response->assertSuccessful();
        // Should have at least 2 shifts: original and the duplicate
        $this->assertGreaterThanOrEqual(2, Shift::query()->count());
    }

    #[Test]
    public function admin_bulk_duplicate_with_unknown_ids_is_noop(): void
    {
        $this->actingAsAdmin();
        $countBefore = Shift::query()->count();

        $response = $this->postJson(route('shifts.multi.duplicate'), [
            'shift_ids' => [PHP_INT_MAX, PHP_INT_MAX - 1],
        ]);

        $response->assertSuccessful();
        $this->assertSame($countBefore, Shift::query()->count());
    }

    #[Test]
    public function admin_bulk_duplicate_with_empty_payload_is_noop(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shifts.multi.duplicate'), ['shift_ids' => []]);

        $response->assertSuccessful();
    }

    #[Test]
    public function guest_cannot_save_multi_edit(): void
    {
        $this->postJson(route('shift.multi.edit.save'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_save_multi_edit_with_empty_shifts_returns_falsy(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('shift.multi.edit.save'), [
            'shiftsToHandle' => ['assignToShift' => [], 'removeFromShift' => []],
            'userType' => 0,
        ]);

        $response->assertSuccessful();
        $this->assertContains(trim($response->getContent()), ['', 'false', '0']);
    }

    #[Test]
    public function admin_save_multi_edit_with_unknown_user_type_returns_falsy(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('shift.multi.edit.save'), [
            'shiftsToHandle' => ['assignToShift' => [1], 'removeFromShift' => []],
            'userType' => 99,
        ]);

        $response->assertSuccessful();
        $this->assertContains(trim($response->getContent()), ['', 'false', '0']);
    }

    #[Test]
    public function guest_cannot_update_user_cell(): void
    {
        $this->postJson(route('shift.plan.user.cell.update'), [])
            ->assertUnauthorized();
    }

    #[Test]
    public function admin_update_user_cell_with_empty_entities_is_noop(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson(route('shift.plan.user.cell.update'), [
            'entities' => [],
            'comment' => null,
            'vacation_type' => null,
            'individual_times' => [],
        ]);

        $response->assertSuccessful();
    }
}

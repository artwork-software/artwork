<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Holidays\Models\Holiday;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class HolidayControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_index(): void
    {
        $this->get(route('holidays.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_index(): void
    {
        $this->actingAsAdmin();

        $this->get(route('holidays.index'))->assertOk();
    }

    #[Test]
    public function admin_can_view_management(): void
    {
        $this->actingAsAdmin();

        $this->get(route('holiday.management'))->assertOk();
    }

    #[Test]
    public function guest_cannot_store(): void
    {
        $this->post(route('holidays.store'), ['name' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_store_holiday(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('holidays.store'), [
            'name' => 'New Year',
            'date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'yearly' => true,
            'color' => '#abcdef',
            'treatAsSpecialDay' => false,
            'selectedSubdivisions' => [],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('holidays', ['name' => 'New Year']);
    }

    #[Test]
    public function admin_can_show_holiday(): void
    {
        $this->actingAsAdmin();
        $holiday = Holiday::query()->forceCreate([
            'name' => 'X',
            'date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'yearly' => false,
            'color' => '#abcdef',
        ]);

        $this->get(route('holidays.show', $holiday))->assertOk();
    }

    #[Test]
    public function admin_can_update_holiday(): void
    {
        $this->actingAsAdmin();
        $holiday = Holiday::query()->forceCreate([
            'name' => 'X',
            'date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'yearly' => false,
            'color' => '#abcdef',
        ]);

        $response = $this->patch(route('holidays.update', $holiday), [
            'name' => 'Updated',
            'date' => '2026-02-01',
            'end_date' => '2026-02-01',
            'yearly' => true,
            'color' => '#aabbcc',
            'treatAsSpecialDay' => false,
            'selectedSubdivisions' => [],
        ]);

        $response->assertOk();
        $this->assertSame('Updated', $holiday->fresh()->name);
    }

    #[Test]
    public function admin_can_destroy_holiday(): void
    {
        $this->actingAsAdmin();
        $holiday = Holiday::query()->forceCreate([
            'name' => 'X',
            'date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'yearly' => false,
            'color' => '#abcdef',
        ]);

        $response = $this->delete(route('holidays.destroy', $holiday));

        $response->assertOk();
        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }
}

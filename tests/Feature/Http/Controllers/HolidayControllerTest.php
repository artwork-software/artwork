<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Illuminate\Support\Facades\Http;
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

    /**
     * DP-04: Import setzt treatAsSpecialDay nur für gesetzliche Feiertage (type "Public"),
     * Schulferien ("School") bleiben ohne Flag.
     */
    #[Test]
    public function api_import_flags_only_public_holidays_as_special_days(): void
    {
        $this->actingAsAdmin();
        $subdivision = Subdivision::create([
            'name' => 'Hessen',
            'code' => 'HE',
            'country_code' => 'DE',
        ]);

        $holiday = static fn (string $id, string $name, string $type, string $start, string $end): array => [
            'id' => $id,
            'startDate' => $start,
            'endDate' => $end,
            'type' => $type,
            'name' => [['language' => 'DE', 'text' => $name]],
            'regionalScope' => 'Regional',
            'temporalScope' => 'FullDay',
            'nationwide' => false,
            'subdivisions' => [['code' => 'DE-HE', 'shortName' => 'HE']],
        ];

        Http::fake(function ($request) use ($holiday) {
            if (str_contains($request->url(), 'SchoolHolidays')) {
                return Http::response([
                    $holiday('school-1', 'Sommerferien', 'School', '2026-07-06', '2026-08-14'),
                ]);
            }

            return Http::response([
                $holiday('public-1', 'Tag der Deutschen Einheit', 'Public', '2026-10-03', '2026-10-03'),
            ]);
        });

        $response = $this->post(route('holiday.api.call'), [
            'selectedSubdivisions' => [['id' => $subdivision->id, 'code' => 'HE']],
            'public_holidays' => true,
            'school_holidays' => true,
            'color' => '#abcdef',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('holidays', [
            'remote_identifier' => 'public-1',
            'treatAsSpecialDay' => true,
            'from_api' => true,
        ]);
        $this->assertDatabaseHas('holidays', [
            'remote_identifier' => 'school-1',
            'treatAsSpecialDay' => false,
            'from_api' => true,
        ]);
    }
}

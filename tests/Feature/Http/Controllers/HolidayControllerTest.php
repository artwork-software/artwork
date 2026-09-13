<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Holidays\Models\Subdivision;
use Artwork\Modules\Permission\Enums\PermissionEnum;
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
            'type' => Holiday::TYPE_PUBLIC,
        ]);
        $this->assertDatabaseHas('holidays', [
            'remote_identifier' => 'school-1',
            'treatAsSpecialDay' => false,
            'from_api' => true,
            'type' => Holiday::TYPE_SCHOOL,
        ]);
    }

    private function holiday(string $name = 'X', bool $special = false, string $type = Holiday::TYPE_CUSTOM): Holiday
    {
        return Holiday::query()->forceCreate([
            'name' => $name,
            'date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'yearly' => false,
            'color' => '#abcdef',
            'treatAsSpecialDay' => $special,
            'type' => $type,
        ]);
    }

    /**
     * Manuell angelegte Einträge speichern den gewählten Typ; ohne explizites Häkchen gilt der
     * Typ-Default (nur gesetzliche Feiertage sind Sondertage).
     */
    #[Test]
    public function store_persists_the_type_and_defaults_the_special_day_flag_by_type(): void
    {
        $this->actingAsAdmin();

        $this->post(route('holiday.store'), [
            'name' => 'Hausfeiertag',
            'date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'yearly' => false,
            'color' => '#abcdef',
            'type' => Holiday::TYPE_PUBLIC,
            'selectedSubdivisions' => [],
        ])->assertOk();

        $this->assertDatabaseHas('holidays', [
            'name' => 'Hausfeiertag',
            'type' => Holiday::TYPE_PUBLIC,
            'treatAsSpecialDay' => true,
            'from_api' => false,
        ]);

        $this->post(route('holiday.store'), [
            'name' => 'Hausferien',
            'date' => '2026-03-02',
            'end_date' => '2026-03-06',
            'yearly' => false,
            'color' => '#abcdef',
            'type' => Holiday::TYPE_SCHOOL,
            'selectedSubdivisions' => [],
        ])->assertOk();

        $this->assertDatabaseHas('holidays', [
            'name' => 'Hausferien',
            'type' => Holiday::TYPE_SCHOOL,
            'treatAsSpecialDay' => false,
        ]);

        // Unbekannter Typ -> custom
        $this->post(route('holiday.store'), [
            'name' => 'Sonstiges',
            'date' => '2026-03-07',
            'end_date' => '2026-03-07',
            'yearly' => false,
            'color' => '#abcdef',
            'type' => 'bogus',
            'selectedSubdivisions' => [],
        ])->assertSessionHasErrors('type');
    }

    #[Test]
    public function management_page_is_forbidden_without_event_settings_or_shift_planning_permission(): void
    {
        $this->actingAsUserWith([]);

        $this->get(route('holiday.management'))->assertForbidden();
    }

    #[Test]
    public function shift_planner_can_view_management_page_read_only(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $this->holiday('Neujahr', true, Holiday::TYPE_PUBLIC);

        $this->get(route('holiday.management'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Holidays/Index')
                ->where('typeFilter', null)
                // Test-DB kann weitere (geseedete) Einträge enthalten: nur den eigenen prüfen
                ->where('holidays.data', fn ($data) => $data->contains(
                    fn ($row) => $row['name'] === 'Neujahr' && $row['type'] === Holiday::TYPE_PUBLIC
                )));
    }

    #[Test]
    public function event_settings_permission_can_view_management_page(): void
    {
        $this->actingAsUserWith(PermissionEnum::EVENT_SETTINGS_UPDATE->value);

        $this->get(route('holiday.management'))->assertOk();
    }

    #[Test]
    public function management_page_filters_by_type(): void
    {
        $this->actingAsAdmin();
        $this->holiday('Neujahr', true, Holiday::TYPE_PUBLIC);
        $this->holiday('Sommerferien', false, Holiday::TYPE_SCHOOL);

        $this->get(route('holiday.management', ['type' => Holiday::TYPE_SCHOOL, 'entitiesPerPage' => 100]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('typeFilter', Holiday::TYPE_SCHOOL)
                ->where('holidays.data', fn ($data) => $data->contains(fn ($row) => $row['name'] === 'Sommerferien')
                    && $data->every(fn ($row) => $row['type'] === Holiday::TYPE_SCHOOL)));

        // Unbekannter Typ -> kein Filter
        $this->get(route('holiday.management', ['type' => 'bogus']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('typeFilter', null));
    }

    /**
     * Dienstplaner:innen dürfen NUR das Sondertag-Flag ändern – alles andere bleibt unberührt.
     */
    #[Test]
    public function shift_planner_can_toggle_only_the_special_day_flag(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $holiday = $this->holiday('Sommerferien', false, Holiday::TYPE_SCHOOL);

        $this->patchJson(route('holiday.special-day.update', $holiday), [
            'treatAsSpecialDay' => true,
            'name' => 'Umbenannt',
            'type' => Holiday::TYPE_PUBLIC,
        ])
            ->assertOk()
            ->assertJson(['id' => $holiday->id, 'treatAsSpecialDay' => true]);

        $fresh = $holiday->fresh();
        $this->assertTrue($fresh->treatAsSpecialDay);
        $this->assertSame('Sommerferien', $fresh->name);
        $this->assertSame(Holiday::TYPE_SCHOOL, $fresh->type);

        $this->patchJson(route('holiday.special-day.update', $holiday), ['treatAsSpecialDay' => false])
            ->assertOk();
        $this->assertFalse($holiday->fresh()->treatAsSpecialDay);
    }

    #[Test]
    public function special_day_endpoint_validates_the_flag(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $holiday = $this->holiday();

        $this->patchJson(route('holiday.special-day.update', $holiday), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('treatAsSpecialDay');
    }

    #[Test]
    public function special_day_endpoint_is_forbidden_without_shift_planning_permission(): void
    {
        $this->actingAsUserWith([]);
        $holiday = $this->holiday('X', false);

        $this->patchJson(route('holiday.special-day.update', $holiday), ['treatAsSpecialDay' => true])
            ->assertForbidden();

        $this->assertFalse($holiday->fresh()->treatAsSpecialDay);
    }

    #[Test]
    public function shift_planner_cannot_use_batch_update_store_update_or_delete(): void
    {
        $this->actingAsUserWith(PermissionEnum::SHIFT_PLANNER->value);
        $holiday = $this->holiday('X', false);

        $this->post(route('holiday.batch-update'), ['holidays' => [$holiday->id => true]])
            ->assertForbidden();
        $this->assertFalse($holiday->fresh()->treatAsSpecialDay);

        $this->post(route('holiday.store'), [
            'name' => 'Neu',
            'date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'yearly' => false,
            'selectedSubdivisions' => [],
        ])->assertForbidden();

        $this->patch(route('holiday.update', $holiday), [
            'name' => 'Umbenannt',
            'date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'yearly' => false,
            'selectedSubdivisions' => [],
        ])->assertForbidden();
        $this->patch(route('holidays.update', $holiday), [
            'name' => 'Umbenannt',
            'date' => '2026-01-01',
            'end_date' => '2026-01-01',
            'yearly' => false,
            'selectedSubdivisions' => [],
        ])->assertForbidden();

        $this->delete(route('holiday.delete', $holiday))->assertForbidden();
        $this->assertDatabaseHas('holidays', ['id' => $holiday->id, 'name' => 'X']);
    }
}

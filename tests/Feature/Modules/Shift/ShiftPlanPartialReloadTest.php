<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Core\Http\Middleware\HandleInertiaRequests;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Der Dienstplan (shifts.plan) liefert seine schweren Props als Closures: der normale Seitenaufruf
 * bekommt sie vollständig, Partial-Reloads (Inertia-Aktionen im Dienstplan mit only: ['flash'])
 * überspringen sie. Vorher lief nach jeder solchen Aktion der komplette Seitenaufbau ein zweites Mal.
 */
final class ShiftPlanPartialReloadTest extends FeatureTestCase
{
    private const LAZY_PROPS = [
        'crafts',
        'eventTypes',
        'eventStatuses',
        'event_properties',
        'first_project_calendar_tab_id',
        'personalFilters',
        'filterOptions',
        'shiftQualifications',
        'dayServices',
        'firstProjectShiftTabId',
        'projectNameUsedForProjectTimePeriod',
        'currentUserCrafts',
        'shiftTimePresets',
        'shiftGroupPresets',
        'globalQualifications',
        'shiftGroups',
    ];

    /**
     * @return array<string, string>
     */
    private function inertiaHeaders(): array
    {
        return [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function flashOnlyHeaders(): array
    {
        return $this->inertiaHeaders() + [
            'X-Inertia-Partial-Component' => 'Shifts/ShiftPlan',
            'X-Inertia-Partial-Data' => 'flash',
        ];
    }

    #[Test]
    public function full_page_visit_resolves_every_lazy_prop(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['shift_plan_daily_view' => false])->save();
        $craft = Craft::factory()->create();

        $response = $this->get(route('shifts.plan'), $this->inertiaHeaders())
            ->assertOk()
            ->assertJsonPath('component', 'Shifts/ShiftPlan');

        $props = $response->json('props');
        foreach (self::LAZY_PROPS as $prop) {
            $this->assertArrayHasKey($prop, $props, $prop);
        }

        $crafts = collect($props['crafts']);
        $this->assertTrue($crafts->contains('id', $craft->id));
        $this->assertArrayHasKey('qualifications', $crafts->firstWhere('id', $craft->id));
        $this->assertIsArray($props['filterOptions']);
        $this->assertIsArray($props['eventTypes']);
    }

    #[Test]
    public function flash_only_reload_skips_the_heavy_props_and_their_queries(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['shift_plan_daily_view' => false])->save();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->get(route('shifts.plan'), $this->flashOnlyHeaders())->assertOk();
        $queries = implode("\n", array_column(DB::getQueryLog(), 'query'));
        DB::disableQueryLog();

        $props = $response->json('props');
        $this->assertArrayHasKey('flash', $props);
        foreach (self::LAZY_PROPS as $prop) {
            $this->assertArrayNotHasKey($prop, $props, $prop);
        }

        $this->assertStringNotContainsString('shift_preset_groups', $queries);
        $this->assertStringNotContainsString('`event_properties`', $queries);
        $this->assertStringNotContainsString('`global_qualifications`', $queries);
    }

    #[Test]
    public function creating_a_shift_with_flash_only_still_delivers_the_created_shift(): void
    {
        $user = $this->actingAsAdmin();
        $user->forceFill(['shift_plan_daily_view' => false])->save();
        $craft = Craft::factory()->create();
        $room = Room::factory()->create();
        $qualification = ShiftQualification::factory()->create();

        $response = $this->withHeaders($this->flashOnlyHeaders())
            ->from(route('shifts.plan'))
            ->followingRedirects()
            ->post(route('event.shift.store.without.event'), [
                'craft_id' => $craft->id,
                'room_id' => $room->id,
                'day' => '2026-10-21',
                'start' => '09:00',
                'end' => '13:00',
                'description' => '',
                'shiftsQualifications' => [
                    ['shift_qualification_id' => $qualification->id, 'value' => 1],
                ],
                'globalQualifications' => [],
            ])
            ->assertOk()
            ->assertJsonPath('component', 'Shifts/ShiftPlan');

        $props = $response->json('props');
        $this->assertNotEmpty($props['flash']['shiftPlanUpdate'] ?? null);
        $this->assertArrayNotHasKey('crafts', $props);
    }
}

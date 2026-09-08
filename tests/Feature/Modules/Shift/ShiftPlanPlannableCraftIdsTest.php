<?php

namespace Tests\Feature\Modules\Shift;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Der Schichtplan (shifts.plan) liefert `plannableCraftIds` (CraftScopeService): Nicht-Admins
 * bekommen nur die Gewerke, die sie festschreiben dürfen (assignable_by_all oder eigene
 * craftShiftPlaner-Zuordnung), Admins null (= alle). Das Festschreibungs-Modal filtert damit
 * seine Gewerksliste, statt bei „alle auswählen" in den 422 aus CommitShiftsRequest zu laufen.
 */
final class ShiftPlanPlannableCraftIdsTest extends FeatureTestCase
{
    #[Test]
    public function non_admin_planner_only_gets_plannable_craft_ids(): void
    {
        $user = $this->actingAsUserWith([
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::CAN_COMMIT_SHIFTS->value,
        ]);

        $assignable = Craft::factory()->create(['assignable_by_all' => true]);
        $own = Craft::factory()->create(['assignable_by_all' => false]);
        $own->craftShiftPlaner()->attach($user->id);
        $foreign = Craft::factory()->create(['assignable_by_all' => false]);

        $response = $this->get(route('shifts.plan'));
        $response->assertOk();

        $ids = $response->inertiaProps('plannableCraftIds');
        $this->assertIsArray($ids);
        $this->assertEqualsCanonicalizing([$assignable->id, $own->id], $ids);
        $this->assertNotContains($foreign->id, $ids);

        // Die volle Gewerksliste bleibt unverändert (Filter, Zeilen, Vorlagen) — nur das Modal filtert
        $craftIds = array_column($response->inertiaProps('crafts'), 'id');
        $this->assertContains($foreign->id, $craftIds);
    }

    #[Test]
    public function admin_gets_null_meaning_all_crafts(): void
    {
        $this->actingAsAdmin();
        Craft::factory()->create(['assignable_by_all' => false]);

        $response = $this->get(route('shifts.plan'));
        $response->assertOk();

        $this->assertNull($response->inertiaProps('plannableCraftIds'));
    }
}

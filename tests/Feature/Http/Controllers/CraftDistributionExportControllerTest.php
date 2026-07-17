<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CraftDistributionExportControllerTest extends FeatureTestCase
{
    #[Test]
    public function itRequiresShiftPlanViewPermission(): void
    {
        $this->actingAsUserWith([]);
        $universalCraft = Craft::factory()->create(['universally_applicable' => true]);

        $this->get(route('shifts.export.craft-distribution', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'universal_craft_id' => $universalCraft->id,
        ]))->assertForbidden();
    }

    #[Test]
    public function itRequiresShiftWorkerHoursPermission(): void
    {
        // Export enthält namentliche Stunden einzelner Personen — Schichtplan-Sicht allein reicht nicht
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value);
        $universalCraft = Craft::factory()->create(['universally_applicable' => true]);

        $this->get(route('shifts.export.craft-distribution', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'universal_craft_id' => $universalCraft->id,
        ]))->assertForbidden();
    }

    #[Test]
    public function itValidatesTheDateRangeAndUniversalCraft(): void
    {
        $this->actingAsUserWith([
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value,
        ]);
        $craft = Craft::factory()->create(['universally_applicable' => false]);

        $this->get(route('shifts.export.craft-distribution', [
            'start_date' => '2026-06-30',
            'end_date' => '2026-06-01',
            'universal_craft_id' => $craft->id,
        ]))->assertSessionHasErrors(['end_date', 'universal_craft_id']);
    }

    #[Test]
    public function itDownloadsTheCraftDistributionWorkbook(): void
    {
        Excel::fake();
        $this->actingAsUserWith([
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::CAN_VIEW_SHIFT_WORKER_HOURS->value,
        ]);
        $universalCraft = Craft::factory()->create(['universally_applicable' => true]);
        $craft = Craft::factory()->create();

        $this->get(route('shifts.export.craft-distribution', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'universal_craft_id' => $universalCraft->id,
            'crafts' => [$craft->id],
        ]))->assertOk();

        Excel::assertDownloaded('craft_distribution_2026-06-01_2026-06-30.xlsx');
    }
}

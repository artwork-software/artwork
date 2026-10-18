<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Enums\BudgetTypeEnum;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\Table;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Kopierte Haupt-/Unterpositionen übernahmen „verifiziert“ ohne Verifizierungs-Datensatz;
 * Zurücknehmen/Entfernen der Verifizierung lief dann mit 500 in eine Null-Dereferenz.
 */
final class BudgetVerificationCopyTest extends FeatureTestCase
{
    private function createTable(): Table
    {
        Event::fake();
        $table = Table::factory()->create(['is_template' => false]);
        Column::factory()->create(['table_id' => $table->id, 'position' => 0]);
        $this->actingAsAdmin();

        return $table;
    }

    #[Test]
    public function duplicated_main_and_sub_positions_are_not_verified(): void
    {
        $table = $this->createTable();
        $main = MainPosition::factory()->create([
            'table_id' => $table->id,
            'is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_CLOSED,
        ]);
        SubPosition::factory()->create([
            'main_position_id' => $main->id,
            'is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_REQUESTED,
        ]);

        $this->post(route('project.budget.main-position.duplicate', $main))->assertOk();

        $copy = MainPosition::query()->where('table_id', $table->id)->where('id', '!=', $main->id)->sole();
        $this->assertSame(
            BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value,
            $this->rawVerified($copy)
        );
        $this->assertSame(
            BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value,
            $this->rawVerified($copy->subPositions()->sole())
        );
        $this->assertSame(BudgetTypeEnum::BUDGET_VERIFIED_TYPE_CLOSED->value, $this->rawVerified($main->fresh()));
    }

    #[Test]
    public function taking_back_an_orphaned_verification_resets_the_state_instead_of_failing(): void
    {
        $table = $this->createTable();
        $main = MainPosition::factory()->create([
            'table_id' => $table->id,
            'is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_REQUESTED,
        ]);
        $sub = SubPosition::factory()->create([
            'main_position_id' => $main->id,
            'is_verified' => BudgetTypeEnum::BUDGET_VERIFIED_TYPE_CLOSED,
        ]);

        $this->post(route('project.budget.take-back.verification'), [
            'type' => 'main',
            'position' => ['id' => $main->id],
        ])->assertOk();
        $this->post(route('project.budget.remove.verification'), [
            'type' => 'sub',
            'position' => ['id' => $sub->id],
        ])->assertOk();

        $this->assertSame(BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value, $this->rawVerified($main->fresh()));
        $this->assertSame(BudgetTypeEnum::BUDGET_VERIFIED_TYPE_NOT_VERIFIED->value, $this->rawVerified($sub->fresh()));
    }

    private function rawVerified(MainPosition|SubPosition $position): string
    {
        $value = $position->getAttribute('is_verified');

        return $value instanceof \BackedEnum ? $value->value : (string) $value;
    }
}

<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Budget\Models\CellCalculation;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class CellCalculationsControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_delete_cell_calculation(): void
    {
        $calculation = CellCalculation::factory()->create();

        $this->delete(route('project.budget.cell.calculation.delete', $calculation))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_delete_cell_calculation(): void
    {
        $this->actingAsAdmin();
        $calculation = CellCalculation::factory()->create();

        $response = $this->delete(route('project.budget.cell.calculation.delete', $calculation));

        $response->assertRedirect();
        $this->assertSoftDeleted('cell_calculations', ['id' => $calculation->id]);
    }
}

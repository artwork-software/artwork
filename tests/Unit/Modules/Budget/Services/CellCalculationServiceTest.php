<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellCalculation;
use Artwork\Modules\Budget\Services\CellCalculationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CellCalculationServiceTest extends TestCase
{
    private CellCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CellCalculationService::class);
    }

    #[Test]
    public function delete_soft_deletes_calculation(): void
    {
        $calculation = CellCalculation::factory()->create();

        $this->service->delete($calculation);

        $this->assertSoftDeleted('cell_calculations', ['id' => $calculation->id]);
    }

    #[Test]
    public function force_delete_removes_calculation(): void
    {
        $calculation = CellCalculation::factory()->create();

        $this->service->forceDelete($calculation);

        $this->assertDatabaseMissing('cell_calculations', ['id' => $calculation->id]);
    }

    #[Test]
    public function update_persists_new_attributes(): void
    {
        $calculation = CellCalculation::factory()->create([
            'name' => 'Old',
            'value' => 100,
        ]);

        $this->service->update($calculation, [
            'name' => 'New',
            'value' => 250,
        ]);

        $this->assertDatabaseHas('cell_calculations', [
            'id' => $calculation->id,
            'name' => 'New',
            'value' => 250,
        ]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted(): void
    {
        $calculation = CellCalculation::factory()->create();
        $calculation->delete();

        $this->service->restore($calculation);

        $this->assertDatabaseHas('cell_calculations', [
            'id' => $calculation->id,
            'deleted_at' => null,
        ]);
    }
}

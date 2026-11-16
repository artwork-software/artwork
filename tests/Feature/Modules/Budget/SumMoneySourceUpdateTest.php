<?php

namespace Tests\Feature\Modules\Budget;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\Project\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Verknüpfung einer Budgetsumme mit einer Finanzierungsquelle ändern
 * (SumDetailsController@update).
 */
final class SumMoneySourceUpdateTest extends FeatureTestCase
{
    private SumMoneySource $sumMoneySource;

    protected function setUp(): void
    {
        parent::setUp();

        $project = Project::factory()->create();
        $table = Table::factory()->create(['project_id' => $project->id, 'is_template' => false]);
        $column = Column::factory()->create(['table_id' => $table->id]);
        $sumDetails = BudgetSumDetails::factory()->create(['column_id' => $column->id]);
        $this->sumMoneySource = SumMoneySource::factory()->create([
            'sourceable_type' => BudgetSumDetails::class,
            'sourceable_id' => $sumDetails->id,
            'linked_type' => 'EARNING',
        ]);
    }

    #[Test]
    public function linking_another_money_source_updates_the_link_and_returns_json(): void
    {
        $this->actingAsAdmin();
        $newMoneySource = MoneySource::factory()->create();

        $this->patchJson(route('project.sum.money.source.update', $this->sumMoneySource->id), [
            'linked_type' => 'COST',
            'money_source_id' => $newMoneySource->id,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sumMoneySource.money_source_id', $newMoneySource->id);

        $this->assertDatabaseHas('sum_money_sources', [
            'id' => $this->sumMoneySource->id,
            'linked_type' => 'COST',
            'money_source_id' => $newMoneySource->id,
        ]);
    }

    #[Test]
    public function non_json_requests_are_redirected_back(): void
    {
        $this->actingAsAdmin();
        $newMoneySource = MoneySource::factory()->create();

        $this->patch(route('project.sum.money.source.update', $this->sumMoneySource->id), [
            'linked_type' => 'COST',
            'money_source_id' => $newMoneySource->id,
        ])->assertRedirect();

        $this->assertSame($newMoneySource->id, (int) $this->sumMoneySource->fresh()->money_source_id);
    }

    #[Test]
    public function an_unknown_money_source_is_rejected_and_keeps_the_existing_link(): void
    {
        $this->actingAsAdmin();
        $previousMoneySourceId = $this->sumMoneySource->money_source_id;

        $this->patchJson(route('project.sum.money.source.update', $this->sumMoneySource->id), [
            'linked_type' => 'COST',
            'money_source_id' => 999999999,
        ])->assertUnprocessable()->assertJsonValidationErrors('money_source_id');

        $this->assertDatabaseHas('sum_money_sources', [
            'id' => $this->sumMoneySource->id,
            'linked_type' => 'EARNING',
            'money_source_id' => $previousMoneySourceId,
        ]);
    }

    #[Test]
    public function the_money_source_can_be_cleared(): void
    {
        $this->actingAsAdmin();

        $this->patchJson(route('project.sum.money.source.update', $this->sumMoneySource->id), [
            'linked_type' => null,
            'money_source_id' => null,
        ])->assertOk();

        $this->assertNull($this->sumMoneySource->fresh()->money_source_id);
    }

    #[Test]
    public function updating_an_unknown_link_returns_404(): void
    {
        $this->actingAsAdmin();

        $this->patchJson(route('project.sum.money.source.update', 999999999), [
            'linked_type' => 'COST',
        ])->assertNotFound();
    }
}

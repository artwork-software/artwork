<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\BudgetSumDetails;
use Artwork\Modules\Budget\Services\BudgetSumDetailsService;
use Artwork\Modules\Budget\Services\SumCommentService;
use Artwork\Modules\Budget\Services\SumMoneySourceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BudgetSumDetailsServiceTest extends TestCase
{
    private BudgetSumDetailsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BudgetSumDetailsService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_record(): void
    {
        $detail = BudgetSumDetails::factory()->create();

        $this->service->softDelete(
            $detail,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertSoftDeleted('budget_sum_details', ['id' => $detail->id]);
    }

    #[Test]
    public function force_delete_removes_record(): void
    {
        $detail = BudgetSumDetails::factory()->create();

        $this->service->forceDelete(
            $detail,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertDatabaseMissing('budget_sum_details', ['id' => $detail->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_record(): void
    {
        $detail = BudgetSumDetails::factory()->create();
        $detail->delete();

        $this->service->restore(
            $detail,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertDatabaseHas('budget_sum_details', [
            'id' => $detail->id,
            'deleted_at' => null,
        ]);
    }
}

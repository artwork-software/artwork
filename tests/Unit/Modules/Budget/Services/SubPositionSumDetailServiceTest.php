<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPositionSumDetail;
use Artwork\Modules\Budget\Services\SubPositionSumDetailService;
use Artwork\Modules\Budget\Services\SumCommentService;
use Artwork\Modules\Budget\Services\SumMoneySourceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SubPositionSumDetailServiceTest extends TestCase
{
    private SubPositionSumDetailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubPositionSumDetailService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_record(): void
    {
        $detail = SubPositionSumDetail::factory()->create();

        $this->service->softDelete(
            $detail,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertSoftDeleted('subposition_sum_details', ['id' => $detail->id]);
    }

    #[Test]
    public function force_delete_removes_record(): void
    {
        $detail = SubPositionSumDetail::factory()->create();

        $this->service->forceDelete(
            $detail,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertDatabaseMissing('subposition_sum_details', ['id' => $detail->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_record(): void
    {
        $detail = SubPositionSumDetail::factory()->create();
        $detail->delete();

        $this->service->restore(
            $detail,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertDatabaseHas('subposition_sum_details', [
            'id' => $detail->id,
            'deleted_at' => null,
        ]);
    }
}

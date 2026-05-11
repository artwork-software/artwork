<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\MainPositionDetails;
use Artwork\Modules\Budget\Services\MainPositionDetailsService;
use Artwork\Modules\Budget\Services\SumCommentService;
use Artwork\Modules\Budget\Services\SumMoneySourceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MainPositionDetailsServiceTest extends TestCase
{
    private MainPositionDetailsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MainPositionDetailsService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_record(): void
    {
        $details = MainPositionDetails::factory()->create();

        $this->service->softDelete(
            $details,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertSoftDeleted('main_position_details', ['id' => $details->id]);
    }

    #[Test]
    public function force_delete_removes_record(): void
    {
        $details = MainPositionDetails::factory()->create();

        $this->service->forceDelete(
            $details,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertDatabaseMissing('main_position_details', ['id' => $details->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_record(): void
    {
        $details = MainPositionDetails::factory()->create();
        $details->delete();

        $this->service->restore(
            $details,
            app(SumCommentService::class),
            app(SumMoneySourceService::class)
        );

        $this->assertDatabaseHas('main_position_details', [
            'id' => $details->id,
            'deleted_at' => null,
        ]);
    }
}

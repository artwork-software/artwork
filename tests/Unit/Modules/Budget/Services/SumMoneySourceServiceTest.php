<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SumMoneySource;
use Artwork\Modules\Budget\Services\SumMoneySourceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SumMoneySourceServiceTest extends TestCase
{
    private SumMoneySourceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SumMoneySourceService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_record(): void
    {
        $source = SumMoneySource::factory()->create();

        $this->service->softDelete($source);

        $this->assertSoftDeleted('sum_money_sources', ['id' => $source->id]);
    }

    #[Test]
    public function force_delete_removes_record(): void
    {
        $source = SumMoneySource::factory()->create();

        $this->service->forceDelete($source);

        $this->assertDatabaseMissing('sum_money_sources', ['id' => $source->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_record(): void
    {
        $source = SumMoneySource::factory()->create();
        $source->delete();

        $this->service->restore($source);

        $this->assertDatabaseHas('sum_money_sources', [
            'id' => $source->id,
            'deleted_at' => null,
        ]);
    }
}

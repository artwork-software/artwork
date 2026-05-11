<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\SubPositionVerified;
use Artwork\Modules\Budget\Services\SubPositionVerifiedService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SubPositionVerifiedServiceTest extends TestCase
{
    private SubPositionVerifiedService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubPositionVerifiedService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_record(): void
    {
        $verified = SubPositionVerified::factory()->create();

        $this->service->softDelete($verified);

        $this->assertSoftDeleted('sub_position_verifieds', ['id' => $verified->id]);
    }

    #[Test]
    public function force_delete_removes_record(): void
    {
        $verified = SubPositionVerified::factory()->create();

        $this->service->forceDelete($verified);

        $this->assertDatabaseMissing('sub_position_verifieds', ['id' => $verified->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_record(): void
    {
        $verified = SubPositionVerified::factory()->create();
        $verified->delete();

        $this->service->restore($verified);

        $this->assertDatabaseHas('sub_position_verifieds', [
            'id' => $verified->id,
            'deleted_at' => null,
        ]);
    }
}

<?php

namespace Tests\Unit\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\MainPositionVerified;
use Artwork\Modules\Budget\Services\MainPositionVerifiedService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MainPositionVerifiedServiceTest extends TestCase
{
    private MainPositionVerifiedService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MainPositionVerifiedService::class);
    }

    #[Test]
    public function soft_delete_soft_deletes_record(): void
    {
        $verified = MainPositionVerified::factory()->create();

        $this->service->softDelete($verified);

        $this->assertSoftDeleted('main_position_verifieds', ['id' => $verified->id]);
    }

    #[Test]
    public function force_delete_removes_record(): void
    {
        $verified = MainPositionVerified::factory()->create();

        $this->service->forceDelete($verified);

        $this->assertDatabaseMissing('main_position_verifieds', ['id' => $verified->id]);
    }

    #[Test]
    public function restore_brings_back_soft_deleted_record(): void
    {
        $verified = MainPositionVerified::factory()->create();
        $verified->delete();

        $this->service->restore($verified);

        $this->assertDatabaseHas('main_position_verifieds', [
            'id' => $verified->id,
            'deleted_at' => null,
        ]);
    }
}

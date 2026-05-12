<?php

namespace Tests\Unit\Modules\User\Services;

use Artwork\Modules\User\Services\UserStatusService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UserStatusServiceTest extends TestCase
{
    private UserStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure chat module is disabled so redis is short-circuited
        config(['app.use_chat_module' => false]);
        $this->service = app(UserStatusService::class);
    }

    #[Test]
    public function get_status_returns_offline_when_chat_module_disabled(): void
    {
        $this->assertSame('offline', $this->service->getStatus(1));
    }

    #[Test]
    public function mark_online_is_noop_when_chat_module_disabled(): void
    {
        // Just verify call does not throw; redis not touched
        $this->service->markOnline(42);

        $this->assertTrue(true);
    }

    #[Test]
    public function mark_offline_is_noop_when_chat_module_disabled(): void
    {
        $this->service->markOffline(42);

        $this->assertTrue(true);
    }
}

<?php

namespace Tests\Unit\Modules\User\Services;

use App\Events\UserStatusUpdated;
use Artwork\Modules\User\Services\UserStatusService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
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

    #[Test]
    public function mark_online_announces_a_user_who_was_not_online_yet(): void
    {
        $this->assertAnnouncedWithRemainingTtl(-2, true);
    }

    #[Test]
    public function mark_online_does_not_announce_again_within_a_minute(): void
    {
        // Middleware läuft auf jedem Request: kein Live-Update je Request
        $this->assertAnnouncedWithRemainingTtl(590, false);
    }

    #[Test]
    public function mark_online_announces_again_after_a_minute(): void
    {
        $this->assertAnnouncedWithRemainingTtl(530, true);
    }

    private function assertAnnouncedWithRemainingTtl(int $remainingTtl, bool $expectAnnouncement): void
    {
        config(['app.use_chat_module' => true]);
        Event::fake([UserStatusUpdated::class]);
        Redis::shouldReceive('ping')->andReturn(true);
        Redis::shouldReceive('ttl')->once()->with('user_status:42')->andReturn($remainingTtl);
        Redis::shouldReceive('setex')->once()->withSomeOfArgs('user_status:42', 600);

        $this->service->markOnline(42);

        if ($expectAnnouncement) {
            Event::assertDispatched(
                UserStatusUpdated::class,
                static fn (UserStatusUpdated $event): bool => $event->userId === 42 && $event->status === 'online'
            );
        } else {
            Event::assertNotDispatched(UserStatusUpdated::class);
        }
    }
}

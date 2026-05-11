<?php

namespace Tests\Unit\Modules\GlobalNotification\Services;

use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GlobalNotificationServiceTest extends TestCase
{
    private GlobalNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(GlobalNotificationService::class);
    }

    #[Test]
    public function create_persists_notification(): void
    {
        $user = \Artwork\Modules\User\Models\User::factory()->create();
        $expiration = new \DateTime('+1 week');

        $notification = $this->service->create(
            title: 'Maintenance',
            description: 'Server maintenance tonight',
            imageName: 'maintenance.png',
            expirationDate: $expiration,
            userId: $user->id,
        );

        $this->assertInstanceOf(GlobalNotification::class, $notification);
        $this->assertTrue($notification->exists);
        $this->assertDatabaseHas('global_notifications', [
            'title' => 'Maintenance',
            'description' => 'Server maintenance tonight',
            'image_name' => 'maintenance.png',
            'created_by' => $user->id,
        ]);
    }

    #[Test]
    public function update_modifies_existing_notification(): void
    {
        $notification = GlobalNotification::factory()->create(['title' => 'Old']);
        $expiration = new \DateTime('+2 days');

        $this->service->update(
            globalNotification: $notification,
            title: 'New',
            description: 'Updated',
            imageName: 'new.png',
            expirationDate: $expiration,
            userId: 42,
        );

        $fresh = $notification->fresh();
        $this->assertSame('New', $fresh->title);
        $this->assertSame('Updated', $fresh->description);
        $this->assertSame('new.png', $fresh->image_name);
        $this->assertSame(42, $fresh->created_by);
    }

    #[Test]
    public function delete_removes_notification(): void
    {
        $notification = GlobalNotification::factory()->create();

        $deleted = $this->service->delete($notification);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('global_notifications', ['id' => $notification->id]);
    }

    #[Test]
    public function enriched_returns_image_url_when_image_present(): void
    {
        GlobalNotification::factory()->create(['image_name' => 'banner.png']);

        $result = $this->service->getGlobalNotificationEnrichedByImageUrl();

        $this->assertArrayHasKey('image_url', $result->toArray());
        $this->assertNotNull($result['image_url']);
    }

    #[Test]
    public function enriched_returns_null_image_url_when_image_absent(): void
    {
        GlobalNotification::factory()->create(['image_name' => null]);

        $result = $this->service->getGlobalNotificationEnrichedByImageUrl();

        $this->assertNull($result['image_url']);
    }
}

<?php

namespace Tests\Unit\App\Support\Services;

use App\Enums\NotificationConstEnum;
use App\Models\User;
use App\Notifications\ShiftNotification;
use App\Support\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Illuminate\Support\Facades\Notification;
use Artwork\Modules\Notification\Models\Notification as ArtworkNotification;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->adminUser();
        $this->actingAs($this->user);
    }

    public function testCreateNotification(): void
    {
        $shift = Shift::factory()->create();
        $targetUser = $this->adminUser();


        $service = app()->get(NotificationService::class);
        $service->setNotificationTo($targetUser);
        $service->setTitle('Test Title');
        $service->setDescription(['Test Description']);
        $service->setNotificationConstEnum(NotificationConstEnum::NOTIFICATION_SHIFT_LOCKED);
        $service->setIcon('green');
        $service->setButtons([]);
        $service->setShowHistory(false);
        $service->setHistoryType('');
        $service->setModelId($shift->id);
        $service->setBroadcastMessage([]);
        $service->setRoomId(null);
        $service->setEventId(null);
        $service->setProjectId(null);
        $service->setDepartmentId(null);
        $service->setTaskId(null);
        $service->setShiftId($shift->id);
        $service->setPriority(0);
        $service->setNotificationKey('');

        Notification::fake();

        $service->createNotification();

        Notification::assertSentTo(
            $targetUser,
            function (ShiftNotification $notification) {
                return $notification->toArray()->title === 'Test Title';
            }
        );
    }
}

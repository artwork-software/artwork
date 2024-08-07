<?php

namespace Tests\Feature\App\Support\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
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
        $service->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_LOCKED);
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

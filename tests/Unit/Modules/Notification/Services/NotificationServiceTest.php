<?php

namespace Tests\Unit\Modules\Notification\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationServiceTest extends TestCase
{
    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationService::class);
    }

    #[Test]
    public function setters_and_getters_round_trip_values(): void
    {
        $user = User::factory()->create();
        $this->service->setNotificationTo($user);
        $this->service->setTitle('Hello');
        $this->service->setIcon('blue');
        $this->service->setPriority(2);
        $this->service->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
        $this->service->setDescription([['type' => 'string', 'title' => 'desc']]);
        $this->service->setButtons(['show_project']);
        $this->service->setShowHistory(true);
        $this->service->setHistoryType('project');
        $this->service->setModelId(123);
        $this->service->setRoomId(11);
        $this->service->setEventId(22);
        $this->service->setProjectId(33);
        $this->service->setDepartmentId(44);
        $this->service->setTaskId(55);
        $this->service->setShiftId(66);
        $this->service->setNotificationKey('key-1');
        $this->service->setBroadcastMessage(['id' => 'abc', 'type' => 'success', 'message' => 'm']);

        $this->assertSame($user->id, $this->service->getNotificationTo()->id);
        $this->assertSame('Hello', $this->service->getTitle());
        $this->assertSame('blue', $this->service->getIcon());
        $this->assertSame(2, $this->service->getPriority());
        $this->assertSame(NotificationEnum::NOTIFICATION_PROJECT, $this->service->getNotificationConstEnum());
        $this->assertCount(1, $this->service->getDescription());
        $this->assertSame(['show_project'], $this->service->getButtons());
        $this->assertTrue($this->service->isShowHistory());
        $this->assertSame('project', $this->service->getHistoryType());
        $this->assertSame(123, $this->service->getModelId());
        $this->assertSame(11, $this->service->getRoomId());
        $this->assertSame(22, $this->service->getEventId());
        $this->assertSame(33, $this->service->getProjectId());
        $this->assertSame(44, $this->service->getDepartmentId());
        $this->assertSame(55, $this->service->getTaskId());
        $this->assertSame(66, $this->service->getShiftId());
        $this->assertSame('key-1', $this->service->getNotificationKey());
        $this->assertSame('m', $this->service->getBroadcastMessage()['message']);
    }

    #[Test]
    public function clear_notification_data_resets_state(): void
    {
        $this->service->setTitle('Old');
        $this->service->setIcon('red');
        $this->service->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
        $this->service->setShowHistory(true);
        $this->service->setHistoryType('something');
        $this->service->setModelId(99);
        $this->service->setButtons(['x', 'y']);

        $this->service->clearNotificationData();

        $this->assertSame('', $this->service->getTitle());
        $this->assertSame('gray', $this->service->getIcon());
        $this->assertNull($this->service->getNotificationConstEnum());
        $this->assertFalse($this->service->isShowHistory());
        $this->assertSame('', $this->service->getHistoryType());
        $this->assertNull($this->service->getModelId());
        $this->assertSame([], $this->service->getButtons());
    }

    #[Test]
    public function set_position_verify_request_id_and_type_are_chainable(): void
    {
        $result = $this->service
            ->setPositionVerifyRequestId(5)
            ->setPositionVerifyRequestType('budget');

        $this->assertSame($this->service, $result);
        $this->assertSame(5, $this->service->getPositionVerifyRequestId());
        $this->assertSame('budget', $this->service->getPositionVerifyRequestType());
    }
}

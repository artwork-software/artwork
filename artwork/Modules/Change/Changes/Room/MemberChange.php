<?php

namespace Artwork\Modules\Change\Changes\Room;

use Artwork\Modules\Change\Interfaces\RoomChange;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;

readonly class MemberChange implements RoomChange
{
    public function __construct(
        private NotificationService $notificationService,
        private ChangeService $changeService
    ) {
    }

    public function change(Room $room, Room $oldRoom): void
    {
        $roomAdminIdsBefore = [];
        $roomAdminIdsAfter = [];
        foreach ($oldRoom->admins as $roomAdminBefore) {
            $roomAdminIdsBefore[] = $roomAdminBefore->id;
        }

        foreach ($room->admins as $roomAdminAfter) {
            $roomAdminIdsAfter[] = $roomAdminAfter->id;
            if (!in_array($roomAdminAfter->id, $roomAdminIdsBefore, true)) {
                $user = User::find($roomAdminAfter->id);
                $notificationTitle = __(
                    'notification.room.leader.add',
                    ['room' => $room->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => rand(1, 1000000),
                    'type' => 'success',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('green');
                $this->notificationService->setPriority(3);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Added as room admin')
                        ->setTranslationKeyPlaceholderValues([$user->first_name])
                );
            }
        }

        // check if user remove as room admin
        foreach ($roomAdminIdsBefore as $roomAdminBefore) {
            if (!in_array($roomAdminBefore, $roomAdminIdsAfter, true)) {
                $user = User::find($roomAdminBefore);
                $notificationTitle = __(
                    'notification.room.leader.remove',
                    ['room' => $room->name],
                    $user->language
                );
                $broadcastMessage = [
                    'id' => random_int(1, 1000000),
                    'type' => 'error',
                    'message' => $notificationTitle
                ];
                $this->notificationService->setTitle($notificationTitle);
                $this->notificationService->setIcon('red');
                $this->notificationService->setPriority(2);
                $this->notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_ROOM_CHANGED);
                $this->notificationService->setBroadcastMessage($broadcastMessage);
                $this->notificationService->setNotificationTo($user);
                $this->notificationService->createNotification();
                $this->changeService->saveFromBuilder(
                    $this->changeService
                        ->createBuilder()
                        ->setModelClass(Room::class)
                        ->setModelId($room->id)
                        ->setTranslationKey('Removed as room admin')
                        ->setTranslationKeyPlaceholderValues([$user->first_name])
                );
            }
        }
    }
}

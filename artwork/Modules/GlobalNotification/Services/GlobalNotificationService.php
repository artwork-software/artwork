<?php

namespace Artwork\Modules\GlobalNotification\Services;

use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\GlobalNotification\Repositories\GlobalNotificationRepository;
use DateTime;
use Illuminate\Filesystem\FilesystemManager;

class GlobalNotificationService
{
    public function __construct(
        private readonly GlobalNotificationRepository $globalNotificationRepository,
        private readonly FilesystemManager $filesystemManager
    ) {
    }

    public function getGlobalNotificationEnrichedByImageUrl(): array|GlobalNotification
    {
        $globalNotification = $this->globalNotificationRepository->getFirst();

        $globalNotification['image_url'] = $globalNotification?->image_name ?
            $this->filesystemManager->disk('public')->url($globalNotification->image_name) :
            null;

        return $globalNotification;
    }

    public function create(
        string $title,
        string $description,
        string $imageName,
        DateTime $expirationDate,
        int $userId
    ): GlobalNotification {
        $globalNotification = $this->globalNotificationRepository->getNewModelInstance(
            [
                'title' => $title,
                'description' => $description,
                'image_name' => $imageName,
                'expiration_date' => $expirationDate,
                'created_by' => $userId
            ]
        );

        $this->globalNotificationRepository->save($globalNotification);

        return $globalNotification;
    }

    public function update(
        GlobalNotification $globalNotification,
        string $title,
        string $description,
        string $imageName,
        DateTime $expirationDate,
        int $userId
    ): GlobalNotification {
        $this->globalNotificationRepository->update(
            $globalNotification,
            [
                'title' => $title,
                'description' => $description,
                'image_name' => $imageName,
                'expiration_date' => $expirationDate,
                'created_by' => $userId
            ]
        );

        return $globalNotification;
    }

    public function delete(
        GlobalNotification $globalNotification
    ): bool {
        return $this->globalNotificationRepository->delete($globalNotification);
    }
}

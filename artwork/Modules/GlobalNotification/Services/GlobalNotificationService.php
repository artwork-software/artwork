<?php

namespace Artwork\Modules\GlobalNotification\Services;

use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\GlobalNotification\Repositories\GlobalNotificationRepository;
use Illuminate\Support\Facades\Storage;

class GlobalNotificationService
{
    public function __construct(private readonly GlobalNotificationRepository $globalNotificationRepository)
    {
    }

    public function getGlobalNotificationEnrichedByImageUrl(): array|GlobalNotification
    {
        $globalNotification = $this->globalNotificationRepository->getFirst();

        $globalNotification['image_url'] = $globalNotification?->image_name ?
            Storage::disk('public')->url($globalNotification->image_name) :
            null;

        return $globalNotification;
    }

}

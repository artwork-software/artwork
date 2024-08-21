<?php

namespace Artwork\Modules\GlobalNotification\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Modules\GlobalNotification\Http\Requests\StoreGlobalNotificationRequest;
use Artwork\Modules\GlobalNotification\Http\Requests\UpdateGlobalNotificationRequest;
use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Artwork\Modules\User\Services\UserService;
use DateTime;
use Exception;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class GlobalNotificationController extends Controller
{
    public function __construct(
        private readonly GlobalNotificationService $globalNotificationService,
        private readonly Redirector $redirector,
        private readonly UserService $userService,
        private readonly FilesystemManager $filesystemManager
    ) {
    }

    /**
     * @throws Exception
     */
    public function store(
        StoreGlobalNotificationRequest $request
    ): RedirectResponse {
        $this->globalNotificationService->create(
            $request->string('notificationName'),
            $request->string('notificationDescription'),
            $request
                ->file('notificationImage')
                ?->storePublicly('notificationImage', ['disk' => 'public']) ?? '',
            new DateTime(
                $request->string('notificationDeadlineDate') .
                ' ' .
                $request->string('notificationDeadlineTime')
            ),
            $this->userService->getAuthUser()->getAttribute('id')
        );

        return $this->redirector->back();
    }

    /**
     * @throws Exception
     */
    public function update(
        GlobalNotification $globalNotification,
        UpdateGlobalNotificationRequest $request
    ): RedirectResponse {
        $fileUrl = ($fileUrl = $request->string('notificationImage'))->contains(['https://', 'http://']) ?
            substr($fileUrl, strpos($fileUrl, 'notificationImage')) :
            $request->file('notificationImage')
                ?->storePublicly('notificationImage', ['disk' => 'public']) ?? '';

        if ($fileUrl !== ($notificationImage = $globalNotification->getAttribute('image_name'))) {
            $publicDisk = $this->filesystemManager->disk('public');
            if ($publicDisk->exists($notificationImage)) {
                $publicDisk->delete($notificationImage);
            }
        }

        $this->globalNotificationService->update(
            $globalNotification,
            $request->string('notificationName'),
            $request->string('notificationDescription'),
            $fileUrl,
            new DateTime(
                $request->string('notificationDeadlineDate') .
                ' ' .
                $request->string('notificationDeadlineTime')
            ),
            $this->userService->getAuthUser()->getAttribute('id')
        );

        return $this->redirector->back();
    }

    public function destroy(GlobalNotification $globalNotification): RedirectResponse
    {
        $globalNotification->delete();

        return $this->redirector->back();
    }
}

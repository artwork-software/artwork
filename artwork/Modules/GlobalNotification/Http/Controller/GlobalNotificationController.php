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
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;

class GlobalNotificationController extends Controller
{
    public function __construct(
        private readonly GlobalNotificationService $globalNotificationService,
        private readonly Redirector $redirector,
        private readonly UserService $userService
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

        Log::debug($fileUrl);

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

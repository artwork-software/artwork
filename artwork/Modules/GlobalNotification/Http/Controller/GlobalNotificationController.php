<?php

namespace Artwork\Modules\GlobalNotification\Http\Controller;

use App\Http\Controllers\Controller;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\GlobalNotification\Http\Requests\StoreGlobalNotificationRequest;
use Artwork\Modules\GlobalNotification\Http\Requests\UpdateGlobalNotificationRequest;
use Artwork\Modules\GlobalNotification\Models\GlobalNotification;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Artwork\Modules\User\Services\UserService;
use DateTime;
use Exception;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
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
        $image = $request->file('notificationImage');

        $this->globalNotificationService->create(
            $request->string('notificationName'),
            $request->string('notificationDescription'),
            $image instanceof UploadedFile
                ? $image->storeAs('notificationImage', StoredFileName::forUpload($image), ['disk' => 'public'])
                : '',
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
        $image = $request->file('notificationImage');
        $notificationImage = (string) $globalNotification->getAttribute('image_name');

        // Ein mitgeschickter URL-String heißt „Bild unverändert“. Er darf nur auf das gespeicherte Bild
        // zeigen – sonst ließe sich mit ../ ein beliebiger Pfad auf der public-Disk eintragen und beim
        // nächsten Speichern löschen.
        $sentUrl = $request->string('notificationImage');
        if ($sentUrl->contains(['https://', 'http://'])) {
            $sentPath = substr($sentUrl, strpos($sentUrl, 'notificationImage'));
            $fileUrl = $sentPath === $notificationImage ? $notificationImage : '';
        } else {
            $fileUrl = $image instanceof UploadedFile
                ? $image->storeAs('notificationImage', StoredFileName::forUpload($image), ['disk' => 'public'])
                : '';
        }

        if ($fileUrl !== $notificationImage && $this->isOwnNotificationImage($notificationImage)) {
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

    private function isOwnNotificationImage(string $path): bool
    {
        return str_starts_with($path, 'notificationImage/') && !str_contains($path, '..');
    }

    public function destroy(GlobalNotification $globalNotification): RedirectResponse
    {
        $globalNotification->delete();

        return $this->redirector->back();
    }
}

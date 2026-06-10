<?php

namespace Artwork\Modules\Notification\Jobs;

use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Archives (sets read_at) all archivable unread notifications of a user in the background.
 *
 * Users with very many notifications (e.g. 10k+ after a bulk event deletion) would otherwise
 * block the request / queue worker when "Archive all" is pressed, so the chunked bulk update is
 * queued here. ShouldBeUnique prevents stacking duplicate archive runs for the same user+group.
 */
class ArchiveUserNotificationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(private readonly int $userId, private readonly ?string $groupType = null)
    {
    }

    public function uniqueId(): string
    {
        return $this->userId . ':' . ($this->groupType ?? 'all');
    }

    public function handle(DatabaseNotificationService $databaseNotificationService): void
    {
        $user = User::find($this->userId);

        if ($user === null) {
            return;
        }

        $databaseNotificationService->archiveAllUnreadForUser($user, $this->groupType);
    }
}

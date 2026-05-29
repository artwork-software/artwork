<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessNotificationRecipient;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves the internal User recipients for external-access notifications.
 * Central place for "who gets notified", including deduplication.
 */
class ExternalNotificationRecipientResolver
{
    /**
     * Inviter + blanket recipients configured for the CRM-submitted type.
     *
     * @return Collection<int, User>
     */
    public function resolveForCrmSubmission(ExternalAccess $external): Collection
    {
        $recipients = collect();

        $inviter = $external->invitedBy;
        if ($inviter) {
            $recipients->push($inviter);
        }

        $recipients = $recipients->merge(
            $this->resolveBlanketRecipients(NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED)
        );

        return $this->dedupeAndFilter($recipients);
    }

    /**
     * Inviter + project managers (project_user.is_manager) + blanket recipients.
     *
     * @return Collection<int, User>
     */
    public function resolveForTabComponentUpdate(ExternalAccess $external, Project $project): Collection
    {
        $recipients = collect();

        $inviter = $external->invitedBy;
        if ($inviter) {
            $recipients->push($inviter);
        }

        $recipients = $recipients->merge($project->managerUsers);

        $recipients = $recipients->merge(
            $this->resolveBlanketRecipients(NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED)
        );

        return $this->dedupeAndFilter($recipients);
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveBlanketRecipients(NotificationEnum $type): Collection
    {
        $entries = ExternalAccessNotificationRecipient::query()
            ->whereJsonContains('notification_types', $type->value)
            ->with('recipient')
            ->get();

        return $entries->flatMap(function (ExternalAccessNotificationRecipient $entry) {
            $recipient = $entry->recipient;
            if ($recipient instanceof User) {
                return collect([$recipient]);
            }
            if ($recipient instanceof Department) {
                return $recipient->users;
            }
            return collect();
        });
    }

    /**
     * @param Collection<int, mixed> $recipients
     * @return Collection<int, User>
     */
    private function dedupeAndFilter(Collection $recipients): Collection
    {
        return $recipients
            ->filter(fn ($u) => $u instanceof User && $u->exists)
            ->unique('id')
            ->values();
    }
}

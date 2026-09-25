<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Notifications\ExternalReviewResultNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalTabReviewResultNotification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;

class ExternalNotificationSender
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ExternalNotificationRecipientResolver $recipientResolver,
    ) {
    }

    public function notifyCrmSubmissionCreated(
        ExternalPendingSubmission $submission,
        bool $isFirstSubmission,
    ): void {
        $external = $submission->externalAccess;
        $recipients = $this->recipientResolver->resolveForCrmSubmission($external);

        $name = $external->displayName();
        $title = $isFirstSubmission
            ? __(':name has filled in their data for the first time.', ['name' => $name])
            : __(':name has updated their data.', ['name' => $name]);

        $description = [
            ['type' => 'string', 'title' => __('Email') . ': ' . $external->email, 'href' => null],
            [
                'type' => 'link',
                'title' => __('Review submission'),
                'href' => route('crm.contacts.external-submissions.show', [
                    $external->crm_contact_id,
                    $submission->id,
                ]),
            ],
        ];

        $this->dispatchToAll(
            $recipients,
            $title,
            $description,
            NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED,
        );
    }

    /**
     * Sammelbenachrichtigung nach "Daten absenden" im Tab (nicht mehr pro Feld).
     */
    public function notifyTabSubmitted(
        ExternalAccess $external,
        Project $project,
        ProjectTab $tab,
        int $changedComponents,
        int $createdContacts = 0,
    ): void {
        $recipients = $this->recipientResolver->resolveForTabComponentUpdate($external, $project);

        $name = $external->displayName();
        $title = __(':name has submitted their data for tab ":tab" in project ":project".', [
            'name' => $name,
            'tab' => $tab->name,
            'project' => $project->name,
        ]);

        $description = [
            ['type' => 'string', 'title' => __('Email') . ': ' . $external->email, 'href' => null],
            [
                'type' => 'string',
                'title' => __(':count field(s) changed since the last submission', ['count' => $changedComponents]),
                'href' => null,
            ],
            [
                'type' => 'string',
                'title' => __(':count contact(s) added', ['count' => $createdContacts]),
                'href' => null,
            ],
            [
                'type' => 'string',
                'title' => __('Please review the data and confirm it in the tab or return it for revision.'),
                'href' => null,
            ],
            [
                'type' => 'link',
                'title' => __('Open project'),
                'href' => route('projects.tab', [$project->id, $tab->id]),
            ],
        ];

        $this->dispatchToAll(
            $recipients,
            $title,
            $description,
            NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED,
            $project->id,
        );
    }

    public function notifyScopeExpiring(ExternalAccessScope $scope): void
    {
        $external = $scope->externalAccess;
        $recipients = $this->recipientResolver->resolveForExpiry($external, $scope->grantedBy);

        $name = $external->displayName();
        $title = __('The tab access of :name to ":tab" in project ":project" expires on :date.', [
            'name' => $name,
            'tab' => $scope->projectTab?->name,
            'project' => $scope->project?->name,
            'date' => $scope->valid_to->format('d.m.Y'),
        ]);

        $description = [
            [
                'type' => 'link',
                'title' => __('Manage external access'),
                'href' => route('crm.external-access.show', $external->id),
            ],
        ];

        $this->dispatchToAll(
            $recipients,
            $title,
            $description,
            NotificationEnum::NOTIFICATION_EXTERNAL_ACCESS_EXPIRING,
            $scope->project_id,
        );
    }

    public function notifyCrmAccessExpiring(ExternalAccess $external): void
    {
        $recipients = $this->recipientResolver->resolveForExpiry($external, null);

        $name = $external->displayName();
        $title = __('The CRM access of :name expires on :date.', [
            'name' => $name,
            'date' => $external->crm_access_expires_at?->format('d.m.Y'),
        ]);

        $description = [
            [
                'type' => 'link',
                'title' => __('Manage external access'),
                'href' => route('crm.external-access.show', $external->id),
            ],
        ];

        $this->dispatchToAll(
            $recipients,
            $title,
            $description,
            NotificationEnum::NOTIFICATION_EXTERNAL_ACCESS_EXPIRING,
        );
    }

    /**
     * Mail an die externe Person, wenn ihr abgesendeter Tab bestätigt oder zurückgegeben wurde.
     */
    public function notifyExternalTabReviewed(ExternalAccessScope $scope): void
    {
        $scope->loadMissing(['externalAccess', 'project', 'projectTab', 'reviewedBy']);
        // Sprache der auslösenden Anfrage mitgeben: Externe haben keine Spracheinstellung, und ein
        // Queue-Worker hätte sonst seine eigene (zufällige) Sprache.
        $scope->externalAccess?->notify(
            (new ExternalTabReviewResultNotification($scope))->locale(app()->getLocale())
        );
    }

    public function notifyExternalReviewResult(ExternalPendingSubmission $submission): void
    {
        $submission->externalAccess->notify(new ExternalReviewResultNotification($submission));
    }

    /**
     * @param iterable<User> $recipients
     * @param array<int, array<string, mixed>> $description
     */
    private function dispatchToAll(
        iterable $recipients,
        string $title,
        array $description,
        NotificationEnum $type,
        ?int $projectId = null,
    ): void {
        foreach ($recipients as $recipient) {
            $this->notificationService->clearNotificationData();
            $this->notificationService->setNotificationTo($recipient);
            $this->notificationService->setTitle($title);
            $this->notificationService->setDescription($description);
            $this->notificationService->setNotificationConstEnum($type);
            $this->notificationService->setIcon('blue');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationKey(uniqid('external_', true));
            $this->notificationService->setBroadcastMessage([
                'id' => uniqid('external_', true),
                'type' => 'success',
                'message' => $title,
            ]);

            if ($projectId !== null) {
                $this->notificationService->setProjectId($projectId);
            }

            $this->notificationService->createNotification();
        }
    }
}

<?php

namespace Artwork\Modules\ExternalAccess\Services;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalPendingSubmission;
use Artwork\Modules\ExternalAccess\Notifications\ExternalReviewResultNotification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Component;
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

        $name = $external->crmContact?->display_name ?? $external->email;
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

    public function notifyCrmDirectUpdate(ExternalAccess $external): void
    {
        $recipients = $this->recipientResolver->resolveForCrmSubmission($external);

        $name = $external->crmContact?->display_name ?? $external->email;
        $title = __(':name has updated their data.', ['name' => $name]);

        $description = [
            ['type' => 'string', 'title' => __('Email') . ': ' . $external->email, 'href' => null],
        ];

        $this->dispatchToAll(
            $recipients,
            $title,
            $description,
            NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED,
        );
    }

    public function notifyTabComponentUpdated(
        ExternalAccess $external,
        Project $project,
        ProjectTab $tab,
        Component $component,
    ): void {
        $recipients = $this->recipientResolver->resolveForTabComponentUpdate($external, $project);

        $name = $external->crmContact?->display_name ?? $external->email;
        $title = __(':name updated the component ":component" in project ":project".', [
            'name' => $name,
            'component' => $component->name,
            'project' => $project->name,
        ]);

        $description = [
            ['type' => 'string', 'title' => __('Tab') . ': ' . $tab->name, 'href' => null],
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

<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as CommandAlias;

class SendExternalIssueReturnDueNotificationsCommand extends Command
{
    protected $signature = 'artwork:send-external-issue-return-due-notifications';

    protected $description = 'Notifies the responsible person of external material issues whose return date is reached';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dueIssues = ExternalIssue::query()
            ->with(['issuedBy'])
            ->whereNotNull('return_date')
            ->whereDate('return_date', '<=', Carbon::today())
            ->whereNull('return_notification_sent_at')
            ->whereNull('received_by_id')
            ->whereNull('return_status')
            ->get();

        foreach ($dueIssues as $issue) {
            $responsible = $issue->issuedBy;

            // Ohne verantwortliche Person keine Erinnerung möglich — nicht als
            // versendet markieren, damit sie nach Nachtragen doch noch kommt.
            if ($responsible === null) {
                continue;
            }

            $language = $responsible->language;
            $returnDate = Carbon::parse($issue->return_date)->format('d.m.Y');

            $notificationTitle = __(
                'notification.material.return_due_title',
                ['issueName' => $issue->name],
                $language
            );

            $description = [
                1 => [
                    'type' => 'string',
                    'title' => __(
                        'notification.material.return_due_description',
                        [
                            'externalName' => $issue->external_name,
                            'date' => $returnDate,
                        ],
                        $language
                    ),
                    'href' => null,
                ],
                2 => [
                    'type' => 'link',
                    'title' => __(
                        'notification.material_issue',
                        ['issueName' => $issue->name],
                        $language
                    ),
                    'href' => route('extern-issue-of-material.index', ['issue' => $issue->id]),
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setDescription($description);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(2);
            $this->notificationService->setNotificationConstEnum(
                NotificationEnum::NOTIFICATION_EXTERNAL_ISSUE_RETURN_DUE
            );
            $this->notificationService->setButtons([
                'material_issue_return_confirm',
                'material_issue_return_decline',
            ]);
            $this->notificationService->setModelId($issue->id);
            $this->notificationService->setProjectId($issue->project_id);
            $this->notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => 'error',
                'message' => $notificationTitle,
            ]);
            $this->notificationService->setNotificationTo($responsible);

            // Mail geht synchron raus, nachdem der database-Channel bereits
            // geschrieben hat: Wirft der Transport, darf weder der Rest der
            // Schleife sterben noch das Versendet-Flag fehlen — sonst gibt es
            // täglich Duplikate der In-App-Benachrichtigung.
            try {
                $this->notificationService->createNotification();
            } catch (\Throwable $exception) {
                report($exception);
            } finally {
                $this->notificationService->clearNotificationData();
            }

            $issue->updateQuietly(['return_notification_sent_at' => now()]);
        }

        return CommandAlias::SUCCESS;
    }
}

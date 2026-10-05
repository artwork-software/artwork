<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Core\Notifications\BaseNotification;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Mail\NotificationSummary;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class SendNotificationsEmailSummariesCommand extends Command
{
    protected $signature = 'artwork:send-notifications-email-summaries';

    protected $description = 'Sends summaries of notifications to all users.';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GeneralSettings $generalSettings,
        private readonly UserService $userService,
        private readonly DatabaseNotificationService $databaseNotificationService,
        private readonly Repository $config,
        private readonly NotificationSettingService $notificationSettingService,
        // Contract statt MailManager: unter Mail::fake() liefert der Container MailFake (TypeError)
        private readonly MailFactory $mailManager,
        private readonly Translator $translator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $exitCode = 0;

        foreach ($this->userService->getAllUsers() as $user) {
            try {
                $this->sendNotificationsSummary($user);
            } catch (Throwable $t) {
                $msg = sprintf(
                    'Could not process user: "%d (%s)" for reason: "%s". Continue with next user.',
                    $user->getAttribute('id'),
                    $user->getAttribute('last_name') . ', ' . $user->getAttribute('first_name'),
                    $t->getMessage(),
                );
                $this->logger->error($msg);
                $this->logger->error($t->getTraceAsString());
                $this->error($msg);
                // Ursache auch an Sentry melden – der Scheduler-Wrapper meldet
                // sonst nur "failed with exit code [1]" ohne Root-Cause.
                report($t);

                $exitCode = 1;
            }
        }

        return $exitCode;
    }

    /**
     * @throws Throwable
     */
    protected function sendNotificationsSummary(User $user): void
    {
        $notificationArray = [];
        $language = BaseNotification::languageOf($user);
        foreach ($this->collectNotificationsToSendForUser($user) as $groupType => $notificationsByType) {
            /** @var Collection $notificationCollection */
            foreach ($notificationsByType as $notificationCollection) {
                if (($notificationCollectionCount = $notificationCollection->count()) > 0) {
                    if (!isset($notificationArray[$groupType])) {
                        $notificationArray[$groupType] = [
                            'title' => $this->translator->get(
                                'notification-group-enum.title.' .
                                NotificationGroupEnum::from($groupType)->title(),
                                [],
                                $language
                            ),
                            'count' => 0,
                            'notifications' => [],
                        ];
                    }

                    $notificationArray[$groupType]['notifications'] = array_merge(
                        $notificationArray[$groupType]['notifications'],
                        array_map(
                            function (DatabaseNotification $databaseNotification) {
                                return [
                                    'body' => $databaseNotification['data'],
                                    'model' => $databaseNotification,
                                ];
                            },
                            $notificationCollection->all()
                        )
                    );

                    $notificationArray[$groupType]['count'] += $notificationCollectionCount;
                }
            }
        }

        if (!empty($notificationArray)) {
            // Absender wie bei den Sofort-Mails: Geschäfts-E-Mail aus den Einstellungen, sonst System-Mail
            $businessEmail = (string) $this->generalSettings->__get('business_email');
            $this->mailManager->mailer()->to($user)->send(
                new NotificationSummary(
                    $notificationArray,
                    $user->getAttribute('first_name'),
                    $this->generalSettings->__get('page_title'),
                    $businessEmail !== '' ? $businessEmail : $this->config->get('mail.system_mail'),
                    $this->config->get('mail.fallback_page_title'),
                    $language
                )
            );

            $notificationIds = [];
            foreach ($notificationArray as $group) {
                foreach ($group['notifications'] as $notification) {
                    $notificationIds[] = $notification['model']->getKey();
                }
            }
            $this->databaseNotificationService->markSentInSummary($notificationIds);
        }
    }

    /**
     * @return array<string, array<string, array<int, DatabaseNotification>>>
     */
    protected function collectNotificationsToSendForUser(User $user): array
    {
        $notificationsToSend = [];

        foreach (
            $this->notificationSettingService
                ->getEnabledOfUser($user->getAttribute('id'))
                ->groupBy('group_type')
                ->values() as $notificationSettings
        ) {
            /** @var NotificationSetting $notificationSetting */
            foreach ($notificationSettings as $notificationSetting) {
                $notificationSettingTypeValue = $notificationSetting->getAttribute('type')->value;
                $sendSummary = $notificationSetting->getAttribute('frequency')->isDueOn(Carbon::today());

                if ($sendSummary) {
                    $notifications = $this->userService->getNotReadOfNotificationTypeNotSentInSummaryForUser(
                        $user,
                        $notificationSettingTypeValue
                    );

                    if ($notifications->count() > 0) {
                        // Gruppe aus dem Enum (die gespeicherte Spalte kann veraltet sein)
                        $notificationsToSend[$notificationSetting->getAttribute('type')->groupType()][
                            $notificationSettingTypeValue
                        ] = $notifications;
                    }
                }
            }
        }

        return $notificationsToSend;
    }
}

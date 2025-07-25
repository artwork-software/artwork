<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
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
use Illuminate\Mail\MailManager;
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
        private readonly CarbonService $carbonService,
        private readonly MailManager $mailManager,
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
                                'de'
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
            $this->mailManager->to($user)->send(
                new NotificationSummary(
                    $notificationArray,
                    $user->getAttribute('first_name'),
                    $this->generalSettings->__get('page_title'),
                    $this->config->get('mail.system_mail'),
                    $this->config->get('mail.fallback_page_title')
                )
            );

            $notificationTypesSentOut = [];
            foreach ($notificationArray as $group) {
                foreach ($group['notifications'] as $notification) {
                    $notificationTypesSentOut[] = $notification['model']->getAttribute('data')['type'];
                    $this->databaseNotificationService->updateSentInSummary(
                        $notification['model'],
                        true
                    );
                }
            }

            $nowFormatted = Carbon::now()->format('Y-m-d');
            $notificationEnumsLastSentDates = $user->getAttribute('notification_enums_last_sent_dates');
            foreach (array_unique($notificationTypesSentOut) as $notificationTypeSentOut) {
                $notificationEnumsLastSentDates[$notificationTypeSentOut] = $nowFormatted;
            }

            $this->userService->update(
                $user,
                [
                    'notification_enums_last_sent_dates' => $notificationEnumsLastSentDates,
                ]
            );
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
            $notificationEnumsLastSentDates = $user->getAttribute('notification_enums_last_sent_dates');
            /** @var NotificationSetting $notificationSetting */
            foreach ($notificationSettings as $notificationSetting) {
                $notificationSettingFrequency = $notificationSetting->getAttribute('frequency');
                //skip immediately frequency
                if ($notificationSettingFrequency === NotificationFrequencyEnum::IMMEDIATELY) {
                    continue;
                }
                $notificationSettingTypeValue = $notificationSetting->getAttribute('type')->value;
                $lastDate = $notificationEnumsLastSentDates[$notificationSettingTypeValue] ?? null;

                $sendSummary = is_null($notificationEnumsLastSentDates) || !$lastDate;

                if (!$sendSummary) {
                    $lastDate = match ($notificationSettingFrequency) {
                        NotificationFrequencyEnum::DAILY => $this->carbonService->parseAndAddDay($lastDate),
                        NotificationFrequencyEnum::WEEKLY_TWICE => $this->carbonService->parseAndAddThreeDays(
                            $lastDate
                        ),
                        NotificationFrequencyEnum::WEEKLY_ONCE => $this->carbonService->parseAndAddWeek($lastDate),
                    };

                    $sendSummary = $this->carbonService->getTodayMidnight() >= $lastDate;
                }

                if ($sendSummary) {
                    $notifications = $this->userService->getNotReadOfNotificationTypeNotSentInSummaryForUser(
                        $user,
                        $notificationSettingTypeValue
                    );

                    if ($notifications->count() > 0) {
                        $notificationsToSend[$notificationSetting->getAttribute('group_type')][
                            $notificationSettingTypeValue
                        ] = $notifications;
                    }
                }
            }
        }

        return $notificationsToSend;
    }
}

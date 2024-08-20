<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\DatabaseNotification\Service\DatabaseNotificationService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Mail\NotificationSummary;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
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
                    $notificationArray[$groupType] = [
                        'title' => __(
                            'notification-group-enum.title.' .
                            NotificationGroupEnum::from($groupType)->title(),
                            [],
                            'de'
                        ),
                        'count' => $notificationCollectionCount,
                    ];
                    $notificationArray[$groupType]['notifications'] = array_map(
                        function (DatabaseNotification $databaseNotification) {
                            return [
                                'body' => $databaseNotification['data'],
                                'model' => $databaseNotification,
                            ];
                        },
                        $notificationCollection->all()
                    );
                }
            }
        }

        if (!empty($notificationArray)) {
            Mail::to($user)->send(
                new NotificationSummary(
                    $notificationArray,
                    $user->getAttribute('first_name'),
                    $this->generalSettings->page_title,
                    $this->config->get('mail.system_mail')
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
            $this->userService->update(
                $user,
                [
                    'notification_enums_last_sent_dates' => array_map(
                        function (string $notificationTypeSentOut) use ($nowFormatted): array {
                            return [$notificationTypeSentOut => $nowFormatted];
                        },
                        $notificationTypesSentOut
                    ),
                ]
            );
        }
    }

    /**
     * @return array<string, array<string, array<int, DatabaseNotification>>>
     */
    private function collectNotificationsToSendForUser(User $user): array
    {
        $notificationsToSend = [];

        foreach (
            $this->notificationSettingService
                ->getEnabledOfUser($user->getAttribute('id'))
                ->groupBy('group_type')
                ->values() as $notificationSettings
        ) {
            $notificationEnumsLastSentDates = $user->getAttribute('notification_enums_last_sent_dates');
            foreach ($notificationSettings as $notificationSetting) {
                $notificationSettingType = $notificationSetting->getAttribute('type');
                $lastDate = $notificationEnumsLastSentDates[$notificationSettingType->value] ?? null;
                $sendSummary = is_null($notificationEnumsLastSentDates) || !$lastDate;
                $notificationFrequency = $notificationSetting->getAttribute('frequency')->value;

                if (!$sendSummary) {
                    //daily default
                    $compareDate = Carbon::now()->subDay();
                    switch ($notificationFrequency) {
                        case NotificationFrequencyEnum::WEEKLY_ONCE:
                            $compareDate = Carbon::now()->subWeek();
                            break;
                        case NotificationFrequencyEnum::WEEKLY_TWICE:
                            $compareDate = Carbon::now()->subDays(3);
                            break;
                    }

                    //set last summary sent at to 10 o'clock to make sure sendSummary evaluation works properly
                    $sendSummary = $compareDate <= Carbon::parse($lastDate . '10:00:00');
                }

                if ($sendSummary) {
                    $notifications = $user->notifications()
                        ->whereNull("read_at")
                        ->whereJsonContains("data->type", $notificationSettingType->value)
                        ->where("sent_in_summary", false)
                        ->get();

                    if ($notifications->count() > 0) {
                        $notificationsToSend[$notificationSetting->getAttribute('group_type')][
                            $notificationSetting->getAttribute('type')->value
                        ] = $notifications;
                    }
                }
            }
        }

        return $notificationsToSend;
    }
}

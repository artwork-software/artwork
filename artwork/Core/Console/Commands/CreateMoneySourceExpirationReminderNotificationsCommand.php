<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySourceReminder\Models\MoneySourceReminder;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CreateMoneySourceExpirationReminderNotificationsCommand extends Command
{
    protected $signature = 'artwork:create-money-source-expiration-reminder-notifications';

    protected $description = 'Creates Notifications, based on money_source_reminders-table for money_source_users';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        foreach (
            MoneySourceReminder::query()
                ->where('type', '=', MoneySourceReminder::MONEY_SOURCE_REMINDER_TYPE_EXPIRATION)
                ->where('notification_created', '=', false)
                ->get() as $moneySourceExpirationReminder
        ) {
            /** @var MoneySource $moneySource */
            $moneySource = $moneySourceExpirationReminder->moneySource;

            //continue if funding_end_date does not exist, reminder is handled when its properly set
            if ($moneySource->funding_end_date === null) {
                continue;
            }

            //determine the date on which the notification should be created
            $reminderDeadline = Carbon::parse($moneySource->funding_end_date)
                ->subDays($moneySourceExpirationReminder->value);

            //continue if deadline is greater than today's date
            if ($reminderDeadline > Carbon::today()) {
                continue;
            }

            //sent notifications if responsible users are given, otherwise just handle the expiration-reminder
            $responsibleMoneySourceUsers = $moneySource
                ->users()
                ->wherePivot('competent', '=', true)
                ->get();

            if ($responsibleMoneySourceUsers->count() > 0) {
                $this->createMoneySourceExpirationReminderNotifications($moneySource, $responsibleMoneySourceUsers);
            }

            $moneySourceExpirationReminder->update(['notification_created' => true]);
        }

        return CommandAlias::SUCCESS;
    }

    private function createMoneySourceExpirationReminderNotifications(
        MoneySource $moneySource,
        Collection $responsibleMoneySourceUsers
    ): void {
        $notificationTitle = sprintf(
            'Am %s wird die Förderung "%s" eingestellt.',
            Carbon::parse($moneySource->funding_end_date)->format('d.m.Y'),
            $moneySource->name
        );
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => 'Sollte das Projekt/die Projekte weiter laufen, trage bitte die ' .
                    'Folgefinanzierung ein.',
                'href' => null
            ],
            2 => [
                'type' => 'link',
                'title' => 'Finanzquelle: ' . $moneySource->name,
                'href' => route('money_sources.show', $moneySource->id)
            ]
        ];

        $this->notificationService->setTitle($notificationTitle);
        $this->notificationService->setDescription($notificationDescription);
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_MONEY_SOURCE_EXPIRATION
        );
        $this->notificationService->setBroadcastMessage($broadcastMessage);
        $this->notificationService->setModelId($moneySource->id);

        foreach ($responsibleMoneySourceUsers as $responsibleMoneySourceUser) {
            $this->notificationService->setNotificationTo($responsibleMoneySourceUser);
            $this->notificationService->createNotification();
        }
    }
}

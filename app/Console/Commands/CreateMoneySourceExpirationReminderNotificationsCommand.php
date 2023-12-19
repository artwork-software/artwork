<?php

namespace App\Console\Commands;

use App\Enums\NotificationConstEnum;
use App\Models\MoneySourceReminder;
use App\Support\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CreateMoneySourceExpirationReminderNotificationsCommand extends Command
{
    protected $signature = 'app:create-money-source-expiration-reminder-notifications';

    protected $description = 'Creates Notifications, based on money_source_reminders-table for money_source_users';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        foreach (
            MoneySourceReminder::all()
                 ->where('type', '=', MoneySourceReminder::MONEY_SOURCE_REMINDER_TYPE_EXPIRATION)
                 ->where('notification_created', '=', false) as $moneySourceExpirationReminder
        ) {
            $moneySource = $moneySourceExpirationReminder->moneySource;

            if (
                Carbon::parse($moneySource->funding_end_date)->subDays($moneySourceExpirationReminder->value) >
                Carbon::today()
            ) {
                continue;
            }

            $notificationTitle = sprintf(
                'Am %s wird die Förderung "%s" eingestellt.',
                Carbon::parse($moneySource->funding_end_date)->format('d.m.Y'),
                $moneySource->name
            );
            $broadcastMessage = [
                'id' => rand(1, 1000000),
                'type' => 'success',
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
                    'title' => 'Finanzierungsquelle: ' . $moneySource->name,
                    'href' => route('money_sources.show', $moneySource->id)
                ]
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setIcon('red');
            $this->notificationService->setPriority(3);
            $this->notificationService->setNotificationConstEnum(
                NotificationConstEnum::NOTIFICATION_MONEY_SOURCE_EXPIRATION
            );
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setModelId($moneySource->id);
            foreach ($moneySource->users()->get() as $responsibleMoneySourceUser) {
                $this->notificationService->setNotificationTo($responsibleMoneySourceUser);
                $this->notificationService->createNotification();
            }

            $moneySourceExpirationReminder->update(['notification_created' => true]);
        }

        return CommandAlias::SUCCESS;
    }
}

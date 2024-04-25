<?php

namespace Artwork\Modules\MoneySourceReminder\Services;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Services\MoneySourceCalculationService;
use Artwork\Modules\MoneySourceReminder\Models\MoneySourceReminder;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;

readonly class MoneySourceThresholdReminderService
{
    public function handleThresholdReminders(
        MoneySource $moneySource,
        MoneySourceCalculationService $moneySourceCalculationService,
        NotificationService $notificationService
    ): void {
        //get all money source threshold-reminders ordered ascending by (percentage-)value, means the lowest
        //threshold-reminder is checked first
        $moneySourceThresholdReminders = $moneySource
            ->reminder()
            ->where('type', '=', MoneySourceReminder::MONEY_SOURCE_REMINDER_TYPE_THRESHOLD)
            ->where('notification_created', '=', false)
            ->orderBy('value', 'ASC')
            ->get();

        //early return if there are no threshold-reminders to check
        if ($moneySourceThresholdReminders->count() === 0) {
            return;
        }

        $positionSumPerMoneySource = $moneySourceCalculationService->getPositionSumOfOneMoneySource($moneySource);

        //early return if $positionSumPerMoneySource is positive, there is no need to check the threshold-reminders,
        //because the money source is growing
        if ($positionSumPerMoneySource > 0) {
            return;
        }

        $reachedThresholdReminder = null;
        foreach ($moneySourceThresholdReminders as $moneySourceThresholdReminder) {
            //$positionSumPerMoneySource is a negative value, so we need to add it to the amount in order to subtract
            //otherwise the sign changes to plus (minus and minus is plus) and the value is just added to the amount
            $amountLeft = $moneySource->amount + $positionSumPerMoneySource;
            $percentageLeft = $amountLeft / ($moneySource->amount / 100);

            //if $percentageLeft undercuts given threshold-reminder value create notification for responsible users
            if ($percentageLeft < $moneySourceThresholdReminder->value) {
                //sent notifications if responsible users are given, otherwise just handle the threshold-reminder
                $responsibleMoneySourceUsers = $moneySource
                    ->users()
                    ->wherePivot('competent', '=', true)
                    ->get();

                if ($responsibleMoneySourceUsers->count() > 0) {
                    $this->createMoneySourceThresholdReminderNotifications(
                        $moneySource,
                        $responsibleMoneySourceUsers,
                        $percentageLeft,
                        $notificationService
                    );
                }
                $reachedThresholdReminder = $moneySourceThresholdReminder;

                //break foreach on first reached threshold-reminder, there is no need for creating notifications
                //when for example 50% threshold is reached and a 75% threshold also exists. this is handled
                //in next foreach then.
                break;
            }
        }

        if ($reachedThresholdReminder) {
            foreach ($moneySourceThresholdReminders as $moneySourceThresholdReminder) {
                //if iterated threshold-reminder value is bigger or equal to reached threshold-reminder value
                //set notification_created to true because the threshold-reminder is implicitly reached and no
                //notification should be created for it
                if ($moneySourceThresholdReminder->value >= $reachedThresholdReminder->value) {
                    $moneySourceThresholdReminder->update(['notification_created' => true]);
                }
            }
        }
    }

    private function createMoneySourceThresholdReminderNotifications(
        MoneySource $moneySource,
        Collection $responsibleMoneySourceUsers,
        int $percentageLeft,
        NotificationService $notificationService
    ): void {
        $notificationTitle = 'Finanzquelle läuft aus';
        $broadcastMessage = [
            'id' => rand(1, 1000000),
            'type' => 'error',
            'message' => $notificationTitle
        ];
        $notificationDescription = [
            1 => [
                'type' => 'string',
                'title' => sprintf(
                    'Die Quelle "%s" besitzt nur noch %d%% des Budgets.',
                    $moneySource->name,
                    max($percentageLeft, 0)
                )
            ],
            2 => [
                'type' => 'link',
                'title' => 'Finanzquelle: ' . $moneySource->name,
                'href' => route('money_sources.show', $moneySource->id)
            ]
        ];

        $notificationService->setTitle($notificationTitle);
        $notificationService->setDescription($notificationDescription);
        $notificationService->setIcon('red');
        $notificationService->setPriority(3);
        $notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED
        );
        $notificationService->setBroadcastMessage($broadcastMessage);
        $notificationService->setModelId($moneySource->id);

        foreach ($responsibleMoneySourceUsers as $responsibleMoneySourceUser) {
            $notificationService->setNotificationTo($responsibleMoneySourceUser);
            $notificationService->createNotification();
        }
    }
}

<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\MoneySource\Models\MoneySource;
use Artwork\Modules\MoneySource\Models\MoneySourceReminder;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
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
        $this->notificationService->setIcon('red');
        $this->notificationService->setPriority(3);
        $this->notificationService->setNotificationConstEnum(
            NotificationEnum::NOTIFICATION_MONEY_SOURCE_EXPIRATION
        );
        $this->notificationService->setModelId($moneySource->id);
        $endDate = Carbon::parse($moneySource->funding_end_date)->format('d.m.Y');

        // Texte je Empfänger*in übersetzt (vorher fest deutsch)
        /** @var \Artwork\Modules\User\Models\User $responsibleMoneySourceUser */
        foreach ($responsibleMoneySourceUsers as $responsibleMoneySourceUser) {
            $language = $responsibleMoneySourceUser->language;
            $notificationTitle = __(
                'On :date the funding ":name" ends.',
                ['date' => $endDate, 'name' => $moneySource->name],
                $language
            );
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setDescription([
                1 => [
                    'type' => 'string',
                    'title' => __('If the project(s) continue, please enter the follow-up funding.', [], $language),
                    'href' => null
                ],
                2 => [
                    'type' => 'link',
                    'title' => __('Funding source: :name', ['name' => $moneySource->name], $language),
                    'href' => route('money_sources.show', $moneySource->id)
                ]
            ]);
            $this->notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => 'error',
                'message' => $notificationTitle
            ]);
            $this->notificationService->setNotificationTo($responsibleMoneySourceUser);
            $this->notificationService->createNotification();
        }
    }
}

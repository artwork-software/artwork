<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Core\Notifications\BaseNotification;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Mail\NotificationSummary;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Notification\Support\NotificationMailPresenter;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Repositories\UserRepository;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Translation\Translator;
use Psr\Log\LoggerInterface;
use Throwable;

class SendNotificationsEmailSummariesCommand extends Command
{
    /**
     * Einträge je Gruppe in einer Sammelmail; der Rest erscheint als „und X weitere“ mit Link ins
     * Benachrichtigungscenter (vorher kamen nach langer Pause tausende Einträge in einer Mail).
     */
    public const MAX_ENTRIES_PER_GROUP = 50;

    protected $signature = 'artwork:send-notifications-email-summaries';

    protected $description = 'Sends summaries of notifications to all users.';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GeneralSettings $generalSettings,
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
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
        $notificationIds = [];
        $language = BaseNotification::languageOf($user);

        foreach ($this->dueTypesByGroup($user) as $groupType => $typeValues) {
            $pending = $this->userRepository->pendingSummaryNotificationsQuery($user, $typeValues);
            // Nur die IDs aller offenen Einträge (neueste zuerst); Inhalte nur für die gezeigten
            $pendingIds = (clone $pending)->pluck('id')->all();
            if ($pendingIds === []) {
                continue;
            }

            $shown = (clone $pending)
                ->whereKey(array_slice($pendingIds, 0, self::MAX_ENTRIES_PER_GROUP))
                ->get();

            $notificationArray[$groupType] = [
                'title' => $this->translator->get(
                    'notification-group-enum.title.' . NotificationGroupEnum::from($groupType)->title(),
                    [],
                    $language
                ),
                'count' => count($pendingIds),
                'more' => max(0, count($pendingIds) - $shown->count()),
                'notifications' => $shown->map(
                    static fn (DatabaseNotification $databaseNotification): array => [
                        'body' => $databaseNotification['data'],
                        'model' => $databaseNotification,
                    ]
                )->all(),
            ];
            // auch die nicht gezeigten: sonst käme dieselbe Riesenmail beim nächsten Lauf wieder
            array_push($notificationIds, ...$pendingIds);
        }

        if ($notificationArray === []) {
            return;
        }

        $eventLookups = NotificationMailPresenter::eventLookups(array_merge(...array_map(
            static fn (array $group): array => array_column($group['notifications'], 'body'),
            array_values($notificationArray)
        )));

        // Absender wie bei den Sofort-Mails: Geschäfts-E-Mail aus den Einstellungen, sonst System-Mail
        $businessEmail = (string) $this->generalSettings->__get('business_email');
        $this->mailManager->mailer()->to($user)->send(
            new NotificationSummary(
                $notificationArray,
                $user->getAttribute('first_name'),
                $this->generalSettings->__get('page_title'),
                $businessEmail !== '' ? $businessEmail : $this->config->get('mail.system_mail'),
                $this->config->get('mail.fallback_page_title'),
                $language,
                $eventLookups
            )
        );

        $this->databaseNotificationService->markSentInSummary($notificationIds);
    }

    /**
     * Heute fällige Typen mit E-Mail an, nach Gruppe (aus dem Enum – die gespeicherte Spalte kann
     * veraltet sein), in der Reihenfolge der Einstellungen.
     *
     * @return array<string, array<int, string>>
     */
    protected function dueTypesByGroup(User $user): array
    {
        $dueTypes = [];
        $today = Carbon::today();

        $settings = $this->notificationSettingService->getEnabledOfUser($user->getAttribute('id'));
        /** @var NotificationSetting $notificationSetting */
        foreach ($settings as $notificationSetting) {
            if (!$notificationSetting->getAttribute('frequency')->isDueOn($today)) {
                continue;
            }
            $type = $notificationSetting->getAttribute('type');
            $dueTypes[$type->groupType()][] = $type->value;
        }

        return $dueTypes;
    }
}

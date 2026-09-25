<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * Benachrichtigt Personen, die neu ins Projektteam aufgenommen werden — unabhängig vom
 * Weg (Projekt anlegen, Projektleitung setzen, Schicht, Aufgabe, Checkliste). Die
 * auslösende Person selbst erhält nie eine Benachrichtigung.
 *
 * Tagesweise Projektzuordnungen im Dienstplan benachrichtigen über
 * ProjectDayAssignmentService eigenständig und laufen bewusst nicht hierüber.
 */
class ProjectTeamNotificationService
{
    /**
     * @param iterable<int|string> $userIds nur Personen, die gerade NEU hinzugekommen sind
     */
    public function notifyAddedToTeam(Project $project, iterable $userIds, bool $asManager = false): void
    {
        $actingUser = Auth::user();
        $actingUserId = $actingUser instanceof User ? $actingUser->id : null;

        $recipientIds = collect($userIds)
            ->map(static fn ($userId): int => (int) $userId)
            ->filter(static fn (int $userId): bool => $userId > 0 && $userId !== $actingUserId)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return;
        }

        $projectId = $project->id;

        // Nach der Response: der Mail-Kanal versendet synchron per SMTP und darf
        // speichernde Requests (v. a. Schichtplanung) nicht blockieren
        $this->deferAfterCommit(fn () => $this->sendNow($projectId, $recipientIds, $asManager));
    }

    /**
     * @param array<int, int> $userIds
     */
    private function sendNow(int $projectId, array $userIds, bool $asManager): void
    {
        $project = Project::find($projectId);

        if ($project === null) {
            return;
        }

        $notificationService = app(NotificationService::class);
        $translationKey = $asManager ? 'notification.project.leader.add' : 'notification.project.member.add';

        foreach (User::query()->whereIn('id', $userIds)->get() as $user) {
            $notificationKey = sprintf('project-team-added-%d-%d', $project->id, $user->id);

            // mehrere Wege im selben Vorgang (z. B. Serienschichten) → nur eine ungelesene Benachrichtigung
            if ($this->hasUnreadNotification($user, $notificationKey)) {
                continue;
            }

            $notificationTitle = __($translationKey, ['project' => $project->name], $user->language);

            $notificationService->clearNotificationData();
            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('green');
            $notificationService->setPriority(3);
            $notificationService->setNotificationConstEnum(NotificationEnum::NOTIFICATION_PROJECT);
            $notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => 'success',
                'message' => $notificationTitle,
            ]);
            $notificationService->setProjectId($project->id);
            $notificationService->setNotificationKey($notificationKey);
            $notificationService->setNotificationTo($user);
            $notificationService->createNotification();
        }

        $notificationService->clearNotificationData();
    }

    private function hasUnreadNotification(User $user, string $notificationKey): bool
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->whereJsonContains('data->notificationKey', $notificationKey)
            ->exists();
    }

    /**
     * defer() meint Illuminate\Support\defer (siehe use function oben): das globale
     * defer() wird auf Prod von der Swoole-Extension besetzt. always(), damit die
     * Benachrichtigung auch bei Redirect-/Fehlerantworten nach erfolgreichem Commit rausgeht.
     */
    private function deferAfterCommit(callable $callback): void
    {
        $register = static function () use ($callback): void {
            defer($callback)->always();
        };

        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit($register);
        } else {
            $register();
        }
    }
}

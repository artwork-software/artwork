<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftWorker;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Str;

class ShiftWorkerConfirmationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        protected AuthManager $auth
    ) {
    }

    public function respond(ShiftWorker $pivot, string $status, ?string $comment): void
    {
        if (!$pivot->relationLoaded('shift')) {
            $pivot->load('shift');
        }

        $shift = $pivot->shift;
        $shift->loadMissing('craft');

        $pivot->update([
            'confirmation_status' => $status,
            'confirmation_at' => now(),
            'confirmation_by_user_id' => $this->auth->id(),
            // Kommentar ist nur für Ablehnungen gedacht — bei (erneuter) Zusage
            // würde ein alter Ablehngrund sonst am Pivot kleben bleiben.
            'confirmation_comment' => $status === ShiftWorker::CONFIRMATION_DECLINED ? $comment : null,
        ]);

        $workerName = $this->resolveWorkerName($pivot);

        $this->logConfirmationActivity($shift, $pivot, $status, $comment, $workerName);
        $this->notifyPlanners($shift, $pivot, $status, $comment, $workerName);
    }

    /**
     * Zurücksetzen auf "ausstehend", wenn sich die Zeiten geändert haben —
     * die abgegebene Antwort bezog sich auf eine andere Schichtzeit.
     */
    public function resetConfirmation(ShiftWorker $pivot): void
    {
        if ($pivot->confirmation_status === null) {
            return;
        }

        $pivot->update([
            'confirmation_status' => null,
            'confirmation_at' => null,
            'confirmation_by_user_id' => null,
            'confirmation_comment' => null,
        ]);
    }

    private function resolveWorkerName(ShiftWorker $pivot): string
    {
        $employable = $pivot->employable;

        return $employable?->full_name
            ?? $employable?->name
            ?? '';
    }

    private function logConfirmationActivity(
        Shift $shift,
        ShiftWorker $pivot,
        string $status,
        ?string $comment,
        string $workerName
    ): void {
        $accepted = $status === ShiftWorker::CONFIRMATION_ACCEPTED;

        activity('shift')
            ->performedOn($shift)
            ->causedBy($this->auth->user())
            ->event($accepted ? 'confirmation_accepted' : 'confirmation_declined')
            ->tap(function ($activity) use ($shift, $pivot, $accepted, $comment, $workerName): void {
                $activity->properties = $activity->properties->merge([
                    'translation_key' => $accepted
                        ? '{0} accepted their shift assignment for {1} ({2})'
                        : '{0} declined their shift assignment for {1} ({2})',
                    'translation_key_placeholder_values' => [
                        $workerName,
                        $shift->craft->name,
                        $pivot->craft_abbreviation ?? $shift->craft->abbreviation,
                    ],
                    'confirmation' => [
                        'status' => $accepted
                            ? ShiftWorker::CONFIRMATION_ACCEPTED
                            : ShiftWorker::CONFIRMATION_DECLINED,
                        'comment' => $comment,
                    ],
                    // Gleiche Kontext-/Snapshot-Felder wie die manuellen Einträge in
                    // ShiftWorkerService, damit Chip-Anzeige und Filter funktionieren.
                    'context' => $shift->is_committed
                        ? 'post_commit'
                        : ($shift->in_workflow ? 'in_workflow' : 'normal'),
                    'shift_id' => $shift->id,
                    'craft_id' => $shift->craft_id,
                    'shift_snapshot' => $shift->toActivitySnapshot(),
                ]);
            })
            ->log($accepted ? 'Shift assignment accepted' : 'Shift assignment declined');
    }

    private function notifyPlanners(
        Shift $shift,
        ShiftWorker $pivot,
        string $status,
        ?string $comment,
        string $workerName
    ): void {
        $recipients = collect();

        if ($pivot->assignedBy) {
            $recipients->push($pivot->assignedBy);
        } else {
            // Altbestand ohne assigned_by_user_id (oder Zuweisende gelöscht):
            // an die Schichtplaner:innen des Gewerks eskalieren.
            $recipients = $shift->craft?->craftShiftPlaner ?? collect();
        }

        $accepted = $status === ShiftWorker::CONFIRMATION_ACCEPTED;
        $actor = $this->auth->user();
        $isProxy = !(
            $pivot->employable_type === User::class
            && $actor !== null
            && (int) $pivot->employable_id === (int) $actor->id
        );

        foreach ($recipients->unique('id') as $recipient) {
            if (!$recipient instanceof User) {
                continue;
            }

            $notificationTitle = __(
                $accepted
                    ? 'notification.shift.confirmation_accepted'
                    : 'notification.shift.confirmation_declined',
                [
                    'workerName' => $workerName,
                    'craftAbbreviation' => $shift->craft->abbreviation,
                    'date' => Carbon::parse($shift->start_date ?? $shift->event_start_day)->format('d.m.Y'),
                ],
                $recipient->language
            );

            $description = [
                1 => [
                    'type' => 'string',
                    'title' => __('notification.keyWords.your_shift', [], $recipient->language)
                        . $shift->time_span_label,
                    'href' => null,
                ],
            ];

            if ($comment && !$accepted) {
                $description[2] = [
                    'type' => 'string',
                    'title' => __(
                        'notification.shift.confirmation_comment',
                        ['comment' => $comment],
                        $recipient->language
                    ),
                    'href' => null,
                ];
            }

            if ($isProxy && $actor !== null) {
                $description[3] = [
                    'type' => 'string',
                    'title' => __(
                        'notification.shift.confirmation_by_proxy',
                        ['userName' => $actor->full_name ?? $actor->name ?? ''],
                        $recipient->language
                    ),
                    'href' => null,
                ];
            }

            if ($shift->event?->exists) {
                $this->notificationService->setProjectId($shift->event?->project?->id);
                $this->notificationService->setEventId($shift->event->id);
            }

            $this->notificationService->setShiftId($shift->id);
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon($accepted ? 'green' : 'red');
            $this->notificationService->setPriority($accepted ? 3 : 2);
            $this->notificationService->setNotificationConstEnum(
                NotificationEnum::NOTIFICATION_SHIFT_WORKER_CONFIRMATION
            );
            $this->notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => $accepted ? 'success' : 'error',
                'message' => $notificationTitle,
            ]);
            $this->notificationService->setDescription($description);
            $this->notificationService->setNotificationTo($recipient);
            $this->notificationService->createNotification();
            $this->notificationService->clearNotificationData();
        }
    }
}

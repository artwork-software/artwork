<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftPlanRequest;
use Artwork\Modules\Shift\Models\ShiftPlanRequestDeadlineNotification;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Erinnert die Gewerkeleitung, wenn für eine anstehende Kalenderwoche mit Schichten
 * die Einreichungsfrist (crafts.commit_request_deadline_days, Default 14 Tage vor
 * KW-Beginn) erreicht ist, ohne dass eine Dienstplananfrage gestellt wurde.
 * Pro Gewerk/KW wird genau einmal erinnert (shift_plan_request_deadline_notifications).
 */
class NotifyShiftPlanRequestDeadlineReached extends Command
{
    protected $signature = 'artwork:notify-shift-plan-request-deadline-reached';

    protected $description = 'Notify craft management if the shift plan request deadline '
        . 'for an upcoming calendar week is reached without a request.';

    public function handle(GeneralSettings $generalSettings, NotificationService $notificationService): int
    {
        if (!$generalSettings->shift_commit_workflow_enabled) {
            return self::SUCCESS;
        }

        $today = now()->startOfDay();

        $crafts = Craft::query()
            ->whereNotNull('commit_request_deadline_days')
            ->with(['managingUsers', 'craftShiftPlaner'])
            ->get();

        foreach ($crafts as $craft) {
            $this->processCraft($craft, $today, $notificationService);
        }

        return self::SUCCESS;
    }

    private function processCraft(
        Craft $craft,
        CarbonInterface $today,
        NotificationService $notificationService
    ): void {
        $deadlineDays = (int) $craft->commit_request_deadline_days;

        // Erster Montag >= heute; alle KWs betrachten, deren Frist (Montag - X Tage)
        // bereits erreicht ist, die aber selbst noch nicht begonnen haben.
        $monday = $today->copy()->startOfWeek(CarbonInterface::MONDAY);
        if ($monday->lt($today)) {
            $monday = $monday->addWeek();
        }
        $lastRelevantMonday = $today->copy()->addDays($deadlineDays);

        $weekStarts = [];
        while ($monday->lte($lastRelevantMonday)) {
            $weekStarts[] = $monday;
            $monday = $monday->copy()->addWeek();
        }

        foreach ($weekStarts as $weekStart) {
            $weekNumber = $weekStart->isoWeek;
            $year = $weekStart->isoWeekYear;

            $alreadyNotified = ShiftPlanRequestDeadlineNotification::query()
                ->where('craft_id', $craft->id)
                ->where('week_number', $weekNumber)
                ->where('year', $year)
                ->exists();
            if ($alreadyNotified) {
                continue;
            }

            $hasOpenOrApprovedRequest = ShiftPlanRequest::query()
                ->where('craft_id', $craft->id)
                ->where('week_number', $weekNumber)
                ->where('year', $year)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
            if ($hasOpenOrApprovedRequest) {
                continue;
            }

            // Ohne Schichten in der KW gibt es nichts einzureichen — keine Erinnerung.
            $sunday = $weekStart->copy()->endOfWeek(CarbonInterface::SUNDAY);
            $hasShiftsInWeek = Shift::query()
                ->where('craft_id', $craft->id)
                ->startAndEndDateOverlap($weekStart->toDateString(), $sunday->toDateString())
                ->exists();
            if (!$hasShiftsInWeek) {
                continue;
            }

            $this->notifyCraftManagement(
                $craft,
                $weekNumber,
                $year,
                $weekStart,
                $sunday,
                $deadlineDays,
                $notificationService
            );

            ShiftPlanRequestDeadlineNotification::create([
                'craft_id' => $craft->id,
                'week_number' => $weekNumber,
                'year' => $year,
            ]);
        }
    }

    private function notifyCraftManagement(
        Craft $craft,
        int $weekNumber,
        int $year,
        CarbonInterface $weekStart,
        CarbonInterface $weekEnd,
        int $deadlineDays,
        NotificationService $notificationService
    ): void {
        // Gewerkeleitung; ohne hinterlegte Leitung ersatzweise die Dienstplaner*innen
        // des Gewerks (sie können die Anfrage stellen).
        $recipients = $craft->managingUsers->isNotEmpty()
            ? $craft->managingUsers
            : $craft->craftShiftPlaner;
        $recipients = $recipients->unique('id');

        foreach ($recipients as $user) {
            $notificationTitle = __(
                'notification.shift.commit_request_deadline_title',
                [
                    'craft' => $craft->name,
                    'week' => $weekNumber,
                ],
                $user->language
            );

            $notificationService->setNotificationTo($user);
            $notificationService->setTitle($notificationTitle);
            $notificationService->setIcon('red');
            $notificationService->setPriority(2);
            $notificationService->setNotificationConstEnum(
                NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST
            );
            $notificationService->setBroadcastMessage([
                'id' => Str::uuid()->toString(),
                'type' => 'error',
                'message' => $notificationTitle,
            ]);
            $notificationService->setDescription([
                0 => [
                    'type' => 'text',
                    'title' => __('notification.shift.commit_request_deadline', [
                        'days' => $deadlineDays,
                        'week' => $weekNumber,
                        'year' => $year,
                        'start_time' => $weekStart->format('d.m.Y'),
                        'end_time' => $weekEnd->format('d.m.Y'),
                        'craft' => $craft->name,
                    ], $user->language),
                    'href' => null,
                ],
                1 => [
                    'type' => 'link',
                    'title' => __(
                        'notification.shift.link_label_commit_request_deadline',
                        [],
                        $user->language
                    ),
                    'href' => route('shifts.plan'),
                ],
            ]);
            $notificationService->createNotification();
            $notificationService->clearNotificationData();
        }
    }
}

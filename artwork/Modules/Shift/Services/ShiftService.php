<?php

namespace Artwork\Modules\Shift\Services;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\PresetShift\Models\PresetShift;
use Artwork\Modules\Role\Enums\RoleEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Repositories\ShiftRepository;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use stdClass;

readonly class ShiftService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private EventService $eventService,
        private CraftService $craftService,
        private NotificationService $notificationService
    ) {
    }

    public function getById(int $shiftId): Shift|null
    {
        return $this->shiftRepository->getById($shiftId);
    }

    private function convertStartEndTime(array $data, Event $event): stdClass
    {
        $convertedTime = new StdClass();
        $convertedStartTime = Carbon::parse($data['start']);
        $convertedEndTime = Carbon::parse($data['end']);

        $convertedTime->start = Carbon::parse($event->start_time)->format('Y-m-d');
        if ($convertedEndTime->isBefore($convertedStartTime)) {
            $convertedTime->end = Carbon::parse($event->start_time)->addDay()->format('Y-m-d');
        } else {
            $convertedTime->end = Carbon::parse($event->start_time)->format('Y-m-d');
        }
        return $convertedTime;
    }

    public function createShiftBySeriesEvent(Event $event, array $data, int $craftId): Shift|Model
    {
        $shift = new Shift();
        $shift->start_date = $this->convertStartEndTime($data, $event)->start;
        $shift->end_date = $this->convertStartEndTime($data, $event)->end;
        $shift->start = $data['start'];
        $shift->end = $data['end'];
        $shift->break_minutes = $data['break_minutes'];
        $shift->description = $data['description'];
        $shift->event()->associate($event);
        $shift->craft()->associate($craftId);
        $shift->is_committed = false;
        return $this->shiftRepository->save($shift);
    }

    public function createShift(Event $event, Craft $craft, array $data): Shift|Model
    {
        $shift = new Shift();
        $shift->start_date = $this->convertStartEndTime($data, $event)->start;
        $shift->end_date = $this->convertStartEndTime($data, $event)->end;
        $shift->start = $data['start'];
        $shift->end = $data['end'];
        $shift->break_minutes = $data['break_minutes'];
        $shift->description = $data['description'];
        $shift->event()->associate($event);
        $shift->craft()->associate($craft);
        return $this->shiftRepository->save($shift);
    }

    public function createShiftByRequest(array $data, Event $event): Model|Shift
    {
        return $this->createShift(
            $event,
            $this->craftService->findById($data['craft_id']),
            $data
        );
    }

    public function createAutomatic(Event $event, int $craftId, array $data): Shift|Model
    {
        $shift = new Shift();
        $shift->start_date = $this->convertStartEndTime($data, $event)->start;
        $shift->end_date = $this->convertStartEndTime($data, $event)->end;
        $shift->start = $data['start'];
        $shift->end = $data['end'];
        $shift->break_minutes = $data['break_minutes'];
        $shift->description = $data['description'];
        $shift->event()->associate($event);
        $shift->craft()->associate($craftId);
        return $this->shiftRepository->save($shift);
    }

    public function createFromShiftPresetShiftForEvent(PresetShift $presetShift, Event $event): Shift
    {
        $shift = new Shift([
            'event_id' => $event->id,
            'start_date' => Carbon::parse($event->start_time)->format('Y-m-d'),
            'end_date' => Carbon::parse($event->end_time)->format('Y-m-d'),
            'start' => $presetShift->start,
            'end' => $presetShift->end,
            'break_minutes' => $presetShift->break_minutes,
            'craft_id' => $presetShift->craft_id,
            'description' => $presetShift->description,
            'is_committed' => false
        ]);

        $this->shiftRepository->save($shift);
        return $shift;
    }

    public function createRemovedAllUsersFromShiftHistoryEntry(Shift $shift, ChangeService $changeService): void
    {
        $changeService->saveFromBuilder(
            $changeService
                ->createBuilder()
                ->setType('shift')
                ->setModelClass(Shift::class)
                ->setModelId($shift->id)
                ->setShift($shift)
                ->setTranslationKey('All scheduled employees have been removed from shift')
                ->setTranslationKeyPlaceholderValues([
                    $shift->craft->abbreviation,
                    $shift->event->eventName
                ])
        );
    }

    public function delete(
        Shift $shift,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): bool {
        foreach ($shift->shiftsQualifications as $shiftsQualification) {
            $shiftsQualificationsService->delete($shiftsQualification);
        }

        foreach ($shift->users as $user) {
            $shiftUserService->delete($user->pivot);
        }

        foreach ($shift->freelancer as $freelancer) {
            $shiftFreelancerService->delete($freelancer->pivot);
        }

        foreach ($shift->serviceProvider as $serviceProvider) {
            $shiftServiceProviderService->delete($serviceProvider->pivot);
        }

        return $this->shiftRepository->delete($shift);
    }

    public function deleteShifts(
        Collection|array $shifts,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): void {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->delete(
                $shift,
                $shiftsQualificationsService,
                $shiftUserService,
                $shiftFreelancerService,
                $shiftServiceProviderService
            );
        }
    }

    public function restoreShifts(
        Collection|array $shifts,
        ShiftsQualificationsService $shiftsQualificationsService,
        ShiftUserService $shiftUserService,
        ShiftFreelancerService $shiftFreelancerService,
        ShiftServiceProviderService $shiftServiceProviderService
    ): void {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $shift->restore();
            $shift->shiftsQualifications()->onlyTrashed()->each(
                fn($shiftsQualification) => $shiftsQualificationsService->restore($shiftsQualification)
            );

            // restore shift users and freelancers from pivot table
            $shift->users()->each(
                fn($user) => $shiftUserService->restore($user->pivot)
            );

            $shift->freelancer()->each(
                fn($freelancer) => $shiftFreelancerService->restore($freelancer->pivot)
            );

            $shift->serviceProvider()->each(
                fn($serviceProvider) => $shiftServiceProviderService->restore($serviceProvider->pivot)
            );
        }
    }

    public function forceDelete(Shift $shift): bool
    {
        //relations are deleted on cascade
        return $this->shiftRepository->forceDelete($shift);
    }

    public function forceDeleteShifts(Collection|array $shifts): void
    {
        /** @var Shift $shift */
        foreach ($shifts as $shift) {
            $this->forceDelete($shift);
        }
    }

    public function createInfringementNotification(Shift $shift): void
    {
        $this->notificationService->setIcon('blue');
        $this->notificationService->setPriority(1);
        $this->notificationService
            ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT);

        $this->notificationService->setButtons(['change_shift', 'delete_shift_notification']);
        $this->notificationService->setProjectId($shift->event()->first()->project()->first()->id);
        $this->notificationService->setEventId($shift->event()->first()->id);
        $this->notificationService->setShiftId($shift->id);
        foreach (User::role(RoleEnum::ARTWORK_ADMIN->value)->get() as $authUser) {
            $notificationTitle = __('notification.shift.short_break', [], $authUser->language);
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
                'type' => 'error',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                1 => [
                    'type' => 'string',
                    'title' => __('notification.keyWords.concerns') .
                        $shift->event()->first()->project()->first()->name . ' , ' .
                        $shift->craft()->first()->abbreviation . ' ' .
                        Carbon::parse($shift->start)->format('d.m.Y H:i') . ' - ' .
                        Carbon::parse($shift->end)->format('d.m.Y H:i'),
                    'href' => null
                ],
            ];

            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->setNotificationTo($authUser);
            $this->notificationService->createNotification();
        }
    }
}

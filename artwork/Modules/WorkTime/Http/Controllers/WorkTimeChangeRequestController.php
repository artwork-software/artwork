<?php

namespace Artwork\Modules\WorkTime\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\WorkTime\Http\Requests\StoreWorkTimeChangeRequestRequest;
use Artwork\Modules\WorkTime\Http\Requests\UpdateWorkTimeChangeRequestRequest;
use Artwork\Modules\WorkTime\Models\WorkTimeChangeRequest;
use Artwork\Modules\WorkTime\Repositories\WorkTimeBookingRepository;
use Artwork\Modules\WorkTime\Services\WorkTimeChangeRequestService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Random\RandomException;

class WorkTimeChangeRequestController extends Controller
{

    public function __construct(
        protected WorkTimeChangeRequestService $workTimeChangeRequestService,
        protected WorkTimeBookingRepository $workTimeBookingRepository,
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        $workTimeChangeRequests = WorkTimeChangeRequest::with(['user', 'shift', 'craft'])
            ->where('user_id', auth()->id())
            ->get();

        return Inertia::render('WorkTime/MyRequests', [
            'requests' => $workTimeChangeRequests,
        ]);
    }

    public function received(): \Inertia\Response
    {
        $userId = auth()->id();

        $workTimeChangeRequests = WorkTimeChangeRequest::with(['user', 'shift', 'craft.craftShiftPlaner'])
            ->whereHas('craft.craftShiftPlaner', function ($query) use ($userId): void {
                $query->where('user_id', $userId);
            })
            ->where('status', 'pending')
            ->get();

        return Inertia::render('WorkTime/ReceivedRequests', [
            'requests' => $workTimeChangeRequests,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     * @throws RandomException
     */
    public function store(StoreWorkTimeChangeRequestRequest $request): void
    {
        $workTimeRequest = $this->workTimeChangeRequestService->createChangeRequest($request->validated());

        // send notification to craft planner
        $craftId = $request->input('craft_id');
        $craftShiftPlaner = Craft::findOrFail($craftId)
            ->craftShiftPlaner()
            ->get();

        /** @var User $planner */
        foreach ($craftShiftPlaner as $planner) {
            $notificationTitle = __(
                'notification.shift.worktime-request.new-request',
                [],
                $planner->language
            );
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                0 => [
                    'type' => 'text',
                    'title' => __('notification.shift.worktime-request.old-new-time', [
                        'user' => $workTimeRequest->user->full_name,
                        'start_time' => $workTimeRequest->request_start_time,
                        'end_time' => $workTimeRequest->request_end_time,
                    ], $planner->language),
                    'href' => route('work-time-request.received', [
                        'requestId' => $request->input('id'),
                    ])
                ],
                1 => [
                    'type' => 'link',
                    'title' => __('notification.shift.worktime-request.link-to-request', [], $planner->language),
                    'href' => route('work-time-request.received', [
                        'requestId' => $request->input('id'),
                    ])
                ],
            ];
            $this->notificationService->setNotificationTo($planner);
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_GET_REQUEST);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkTimeChangeRequest $workTimeChangeRequest): void
    {
        //
    }

    public function approve(
        WorkTimeChangeRequest $workTimeChangeRequest,
        WorkTimeBookingRepository $repository
    ): void {
        $shift = $workTimeChangeRequest->shift;
        $user = $workTimeChangeRequest->user;

        $oldPivot = $shift->users()->where('user_id', $user->id)->first()?->pivot;

        if (!$oldPivot) {
            abort(404, 'Pivot-Daten nicht gefunden.');
        }

        $oldStart = \Carbon\Carbon::parse($oldPivot->start_time);
        $oldEnd = \Carbon\Carbon::parse($oldPivot->end_time);
        $newStart = Carbon::parse($workTimeChangeRequest->request_start_time);
        $newEnd = Carbon::parse($workTimeChangeRequest->request_end_time);

        $shiftDate = \Carbon\Carbon::parse($oldPivot->start_date);
        $now = now()->startOfDay();

        $oldDuration = $oldStart->diffInMinutes($oldEnd);
        $newDuration = $newStart->diffInMinutes($newEnd);
        $balanceDelta = $newDuration - $oldDuration;
        if ($shiftDate->gte($now)) {
            $shift->users()->updateExistingPivot($user->id, [
                'start_time' => $newStart->format('H:i:s'),
                'end_time' => $newEnd->format('H:i:s'),
            ]);
        } else {
            $weekdayIndex = $shiftDate->dayOfWeek;
            $previousBooking = $repository->getPreviousBooking($user, $shiftDate, $weekdayIndex);

            if (!$previousBooking) {
                abort(404, 'Es existiert keine ursprüngliche Buchung für diesen Tag.');
            }

            $repository->storeOrUpdateBooking($user, now(), now()->dayOfWeek, [
                'name' => 'adjustment_work_time_change_request_' . $shift->id,
                'comment' => 'Zeitkorrektur: ' . $oldDuration . 'min → ' . $newDuration . 'min',
                'booking_day' => now()->toDateString(),
                'booking_weekday' => now()->dayOfWeek,
                'worked_hours' => 0,
                'wanted_working_hours' => 0,
                'nightly_working_hours' => 0,
                'is_special_day' => false,
                'work_time_balance_change' => $balanceDelta,
                'user_id' => $user->id,
                'booker_id' => auth()->id(),
            ]);

            if ($balanceDelta !== 0) {
                $repository->updateUserBalance($user, $balanceDelta);
            }

            $shift->users()->updateExistingPivot($user->id, [
                'start_time' => $newStart->format('H:i:s'),
                'end_time' => $newEnd->format('H:i:s'),
            ]);
        }

        $workTimeChangeRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);
    }

    public function decline(WorkTimeChangeRequest $workTimeChangeRequest, Request $request): void
    {
        $workTimeChangeRequest->update([
            'status' => 'rejected',
            'declined_by' => auth()->id(),
            'decline_comment' => $request->input('decline_message', 'Keine Begründung angegeben.'),
        ]);
    }
}

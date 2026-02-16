<?php

namespace Artwork\Modules\WorkTime\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Role\Enums\RoleEnum;
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
        $user = auth()->user();

        // Check if user is admin or has shift planner permission
        if (!$user->hasRole(RoleEnum::ARTWORK_ADMIN->value) &&
            !$user->hasPermissionTo(PermissionEnum::SHIFT_PLANNER->value)) {
            abort(403, 'Unauthorized');
        }

        $userId = $user->id;

        $workTimeChangeRequests = WorkTimeChangeRequest::with(['user', 'shift', 'craft.craftShiftPlaner'])
            ->where('status', 'pending')
            ->where(function ($query) use ($userId) {
                // Include requests where user is assigned as craft shift planner
                $query->whereHas('craft.craftShiftPlaner', function ($subQuery) use ($userId): void {
                    $subQuery->where('user_id', $userId);
                })
                // OR include requests from crafts that are assignable by all
                ->orWhereHas('craft', function ($subQuery): void {
                    $subQuery->where('assignable_by_all', true);
                });
            })
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
        $validated = $request->validated();

        // Detect overnight shift: if end_time <= start_time, set end_date to next day
        $startTime = Carbon::createFromFormat('H:i', $validated['request_start_time']);
        $endTime = Carbon::createFromFormat('H:i', $validated['request_end_time']);

        if ($endTime->lte($startTime)) {
            $shift = \Artwork\Modules\Shift\Models\Shift::findOrFail($validated['shift_id']);
            $pivot = $shift->users()
                ->where('shift_workers.employable_id', $validated['user_id'])
                ->first()
                ?->pivot;

            $startDate = $pivot && $pivot->start_date
                ? Carbon::parse($pivot->start_date)
                : Carbon::parse($shift->start);

            $validated['request_end_date'] = $startDate->copy()->addDay()->toDateString();
        }

        $workTimeRequest = $this->workTimeChangeRequestService->createChangeRequest($validated);

        // send notification to craft planner
        $craftId = $request->input('craft_id');
        $craft = Craft::findOrFail($craftId);

        // Determine who should receive notifications
        $recipientPlanners = collect();

        if ($craft->assignable_by_all) {
            // If craft is assignable by all, notify all users with shift planner permission
            $recipientPlanners = User::permission(PermissionEnum::SHIFT_PLANNER->value)->get();
        } else {
            // Otherwise, only notify the assigned craft shift planners
            $recipientPlanners = $craft->craftShiftPlaner()->get();
        }

        /** @var User $planner */
        foreach ($recipientPlanners as $planner) {
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
    public function destroy(WorkTimeChangeRequest $workTimeChangeRequest): \Illuminate\Http\RedirectResponse
    {
        // Check if the user owns this request
        if ($workTimeChangeRequest->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Check if the request is still pending
        if ($workTimeChangeRequest->status !== 'pending') {
            abort(403, 'Only pending requests can be deleted');
        }

        // Delete the request
        $workTimeChangeRequest->delete();

        return redirect()->back();
    }

    public function approve(
        WorkTimeChangeRequest $workTimeChangeRequest,
        WorkTimeBookingRepository $repository
    ): \Illuminate\Http\RedirectResponse {
        $shift = $workTimeChangeRequest->shift;
        $user = $workTimeChangeRequest->user;

        $oldPivot = $shift->users()->where('shift_workers.employable_id', $user->id)->first()?->pivot;

        if (!$oldPivot) {
            abort(404, 'Ursprüngliche Schicht nicht gefunden.');
        }

        $startDateParsed = Carbon::parse($oldPivot->start_date);
        $endDateParsed = Carbon::parse($oldPivot->end_date ?? $oldPivot->start_date);
        $startTimeParsed = Carbon::parse($oldPivot->start_time);
        $endTimeParsed = Carbon::parse($oldPivot->end_time);

        $oldStart = $startDateParsed->copy()->setTimeFrom($startTimeParsed);
        $oldEnd = $endDateParsed->copy()->setTimeFrom($endTimeParsed);
        if ($oldEnd->lte($oldStart)) {
            $oldEnd->addDay();
        }

        $shiftDate = $startDateParsed->copy()->startOfDay();
        $requestStartTimeParsed = Carbon::parse($workTimeChangeRequest->request_start_time);
        $newStart = $shiftDate->copy()->setTimeFrom($requestStartTimeParsed);
        $requestEndDate = $workTimeChangeRequest->request_end_date
            ? Carbon::parse($workTimeChangeRequest->request_end_date)->startOfDay()
            : $shiftDate->copy();
        $requestEndTimeParsed = Carbon::parse($workTimeChangeRequest->request_end_time);
        $newEnd = $requestEndDate->copy()->setTimeFrom($requestEndTimeParsed);

        $now = now()->startOfDay();

        $oldDuration = $oldStart->diffInMinutes($oldEnd);
        $newDuration = $newStart->diffInMinutes($newEnd);
        $balanceDelta = $newDuration - $oldDuration;

        $pivotUpdate = [
            'start_time' => $newStart->format('H:i:s'),
            'end_time' => $newEnd->format('H:i:s'),
        ];

        // Only update end_date when the request crosses midnight
        if ($workTimeChangeRequest->request_end_date) {
            $pivotUpdate['end_date'] = $workTimeChangeRequest->request_end_date;
        }

        if ($shiftDate->gte($now)) {
            $shift->users()->updateExistingPivot($user->id, $pivotUpdate);
        } else {
            // For past shifts, create an adjustment booking to reflect the time change
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

            $shift->users()->updateExistingPivot($user->id, $pivotUpdate);
        }

        $workTimeChangeRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        return redirect()->back();
    }

    public function decline(WorkTimeChangeRequest $workTimeChangeRequest, Request $request): \Illuminate\Http\RedirectResponse
    {
        $workTimeChangeRequest->update([
            'status' => 'rejected',
            'declined_by' => auth()->id(),
            'decline_comment' => $request->input('decline_message', 'Keine Begründung angegeben.'),
        ]);

        return redirect()->back();
    }
}

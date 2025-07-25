<?php

namespace Artwork\Modules\Shift\Http\Controllers;

use App\Http\Controllers\Controller;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Shift\Http\Requests\StoreShiftCommitWorkflowRequestsRequest;
use Artwork\Modules\Shift\Http\Requests\UpdateShiftCommitWorkflowRequestsRequest;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowRequests;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\Shift\Services\ShiftService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

class ShiftCommitWorkflowRequestsController extends Controller
{
    public function __construct(
        protected AuthManager $auth,
        protected ShiftService $shiftService,
        protected NotificationService $notificationService,
    ){
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all shift commit workflow requests
        $requests = ShiftCommitWorkflowRequests::with(['requestedBy', 'approvedBy', 'declinedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Return the view with the requests data
        return inertia('Shifts/ShiftCommitWorkflowRequests/Index', [
            'requests' => $requests,
        ]);
    }

    public function approve(ShiftCommitWorkflowRequests $shiftCommitRequest)
    {
        // Approve the request
        $shiftCommitRequest->update([
            'status' => 'approved',
            'approved_by_id' => $this->auth->id(),
        ]);

        $this->shiftService->commitShiftsByDate(
            Carbon::parse($shiftCommitRequest->start_date),
            Carbon::parse($shiftCommitRequest->end_date)
        );

        // Optionally, you can return a response or redirect
        return back()->with('success', 'Shift commit workflow request approved successfully.');
    }

    public function decline(ShiftCommitWorkflowRequests $shiftCommitRequest, Request $request)
    {
        // Decline the request
        $shiftCommitRequest->update([
            'status' => 'rejected',
            'reason' => $request->get('reason'),
            'declined_by_id' => $this->auth->id(),
        ]);

        // Optionally, you can return a response or redirect
        return back()->with('success', 'Shift commit workflow request declined successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreShiftCommitWorkflowRequestsRequest $request)
    {
        $validatedData = $request->validated();

        // Create a new ShiftCommitWorkflowRequests instance
        $request = ShiftCommitWorkflowRequests::create([
            'requested_by_id' => $this->auth->id(),
            'start_date' => $validatedData['start'],
            'end_date' => $validatedData['end'],
        ]);

        // notify the user where are in ShiftCommitWorkflowUsers
        $usersToNotify = ShiftCommitWorkflowUser::all();

        foreach ($usersToNotify as $user) {
            /** @var User $userToNotify */
            $userToNotify = $user->user;
            $notificationTitle = __(
                'notification.shift.new_commit_request_title',
                [],
                $userToNotify->language
            );
            $broadcastMessage = [
                'id' => random_int(1, 1000000),
                'type' => 'success',
                'message' => $notificationTitle
            ];
            $notificationDescription = [
                0 => [
                    'type' => 'text',
                    'title' => __('notification.shift.new_commit_request', [
                        'user' => $request->requestedBy->full_name,
                        'start_time' => Carbon::parse($request->start_date)->format('d. M Y H:i'),
                        'end_time' => Carbon::parse($request->end_date)->format('d. M Y H:i'),
                    ], $userToNotify->language),
                    'href' => route('shifts.commit-requests.index', [
                        'requestId' => $request->id,
                    ])
                ],
                1 => [
                    'type' => 'link',
                    'title' => __('notification.shift.link_label_new_commit_request', [], $userToNotify->language),
                    'href' => route('shifts.commit-requests.index', [
                        'requestId' => $request->id,
                    ])
                ],
            ];
            $this->notificationService->setNotificationTo($userToNotify);
            $this->notificationService->setTitle($notificationTitle);
            $this->notificationService->setIcon('green');
            $this->notificationService->setPriority(2);
            $this->notificationService
                ->setNotificationConstEnum(NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST);
            $this->notificationService->setBroadcastMessage($broadcastMessage);
            $this->notificationService->setDescription($notificationDescription);
            $this->notificationService->createNotification();
        }

        // Optionally, you can return a response or redirect
        return back()->with('success', 'Shift commit workflow request created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ShiftCommitWorkflowRequests $shiftCommitWorkflowRequests)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ShiftCommitWorkflowRequests $shiftCommitWorkflowRequests)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateShiftCommitWorkflowRequestsRequest $request, ShiftCommitWorkflowRequests $shiftCommitWorkflowRequests)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShiftCommitWorkflowRequests $shiftCommitWorkflowRequests)
    {
        //
    }
}

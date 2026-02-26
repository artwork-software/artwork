<?php

namespace App\Http\Controllers;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Http\Resources\NotificationProjectResource;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;
use Inertia\ResponseFactory;

class NotificationController extends Controller
{
    public function __construct(private readonly VacationService $vacationService)
    {
    }

    //@todo: fix phpcs error - refactor function because complexity is rising
    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function index(
        ProjectTabService $projectTabService,
        GlobalNotificationService $globalNotificationService,
        UserService $userService
    ): Response|ResponseFactory {
        $userService->updateCurrentUserShowNotificationIndicator(
            $userService->getAuthUser(),
            false
        );

        $historyObjects = [];
        $event = null;
        // reload functions
        if (request('showHistory')) {
            if (request('historyType') === 'project') {
                $project = Project::find(request('modelId'));
                if ($project !== null) {
                    $historyComplete = $project->historyChanges()->all();
                    foreach ($historyComplete as $history) {
                        $historyObjects[] = [
                            'changes' => json_decode($history->changes),
                            'change_by' => $history->changer,
                            'created_at' => $history->created_at->diffInHours() < 24
                                ? $history->created_at->diffForHumans()
                                : $history->created_at->format('d.m.Y, H:i'),
                        ];
                    }
                }
            }

            if (request('historyType') === 'event') {
                $event = Event::find(request('modelId'));
                if ($event !== null) {
                    $historyComplete = $event->historyChanges()->all();
                    foreach ($historyComplete as $history) {
                        $historyObjects[] = [
                            'changes' => json_decode($history->changes),
                            'change_by' => $history->changer,
                            'created_at' => $history->created_at->diffInHours() < 24
                                ? $history->created_at->diffForHumans()
                                : $history->created_at->format('d.m.Y, H:i'),
                        ];
                    }
                }
            }

            if (request('historyType') === 'vacations') {
                $vacations = $this->vacationService->findVacationsByUserId(request('modelId'));

                foreach ($vacations as $vacation) {
                    $historyComplete = $vacation->historyChanges()->all();
                    foreach ($historyComplete as $history) {
                        $historyObjects[] = [
                            'changes' => json_decode($history->changes),
                            'change_by' => $history->changer,
                            'created_at' => $history->created_at->diffInHours() < 24
                                ? $history->created_at->diffForHumans()
                                : $history->created_at->format('d.m.Y, H:i'),
                        ];
                    }
                }
            }
        }

        if (request('openDeclineEvent')) {
            $event = Event::find(request('eventId'));
        }

        if (request('openEditEvent')) {
            $event = Event::find(request('eventId'));
        }

        /** @var User $user */
        $user = Auth::user();
        $output = [];
        $outputRead = [];

        foreach ($user->notifications()->get() as $notification) {
            if ($notification->read_at === null) {
                $output[$notification->data['groupType']][] = $notification;
            } else {
                $outputRead[$notification->data['groupType']][] = $notification;
            }
        }

        return inertia('Notifications/Show', [
            'historyObjects' => $historyObjects,
            'event' => $event !== null ? new CalendarEventResource($event) : null,
            'project',
            'wantedSplit',
            'roomCollisions',
            'notifications' => $output,
            'readNotifications' => $outputRead,
            'globalNotification' => $globalNotificationService->getGlobalNotificationEnrichedByImageUrl(),
            'rooms' => RoomIndexWithoutEventsResource::collection(Room::all())->resolve(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => NotificationProjectResource::collection(Project::all())->resolve(),
            'notificationSettings' => $user->notificationSettings()->get()->groupBy("group_type"),
            'notificationFrequencies' => array_map(fn (NotificationFrequencyEnum $frequency) => [
                'title' => $frequency->title(),
                'value' => $frequency->value,
            ], NotificationFrequencyEnum::cases()),
            'groupTypes' => collect(NotificationGroupEnum::cases())->reduce(
                function ($groupTypes, $type) {
                    $groupTypes[$type->value] = [
                        'title' => $type->title(),
                        'description' => $type->description(),
                    ];
                    return $groupTypes;
                },
                []
            ),
            'first_project_shift_tab_id' => $projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::SHIFT_TAB),
            'first_project_budget_tab_id' => $projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::BUDGET),
            'first_project_calendar_tab_id' => $projectTabService
                ->getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum::CALENDAR),
            'eventStatuses' => EventStatus::orderBy('order')->get()
        ]);
    }

    public function setReadAt(
        Request $request,
        DatabaseNotificationService $databaseNotificationService,
        CarbonService $carbonService
    ): void {
        /** @var DatabaseNotification $wantedNotification */
        $wantedNotification = $databaseNotificationService->find($request->string('notificationId'));

        if (is_null($wantedNotification)) {
            return;
        }

        if (count(array_diff($wantedNotification->getAttribute('data')['buttons'], ['showInTasks', 'show_project', 'delete_shift_notification', 'see_shift', 'change_shift'])) > 0) {
            return;
        }

        $wantedNotification->setAttribute('read_at', $carbonService->getNow());
        $wantedNotification->save();
    }

    public function setOnReadAll(Request $request): void
    {
        $user = User::find(Auth::id());

        if ($user === null) {
            return;
        }

        $notifications = $user->notifications()->whereIn('id', $request->notificationIds)->get();
        foreach ($notifications as $notification) {
            if (count(array_diff($notification->data['buttons'], ['showInTasks', 'show_project', 'delete_shift_notification', 'see_shift', 'change_shift'])) > 0) {
                continue;
            }

            $notification->read_at = now();
            $notification->save();
        }
    }

    public function updateSetting(Request $request, NotificationSetting $setting): void
    {
        if (Auth::id() !== $setting->user_id) {
            abort(403);
        }

        $setting->update($request->only("enabled_email", "frequency", "enabled_push"));
    }

    public function toggleGroup(Request $request): void
    {
        Auth::user()->notificationSettings()
            ->where('group_type', $request->groupType)
            ->update($request->only('enabled_email', 'enabled_push'));
    }

    public function destroy(string $id): string
    {
        $user = User::find(Auth::id());
        $notification = $user->notifications->find($id);
        $notification->delete();
        return 'Notification deleted';
    }
}

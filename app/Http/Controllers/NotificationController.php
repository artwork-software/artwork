<?php

namespace App\Http\Controllers;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Casts\TimeAgoCast;
use Artwork\Modules\Change\Services\ChangeService;
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
    public function __construct(
        private readonly VacationService $vacationService,
        private readonly ChangeService $changeService,
    ) {
    }

    /**
     * Paginated today's unread notifications for the dashboard. Loaded page-by-page so a user
     * with thousands of notifications does not blow up the dashboard payload / browser memory.
     */
    public function todayPaginated(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = min(50, max(1, $request->integer('perPage', 5)));

        $notifications = Auth::user()
            ->notifications()
            ->select(['id', 'data->priority as priority', 'data'])
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->withCasts(['created_at' => TimeAgoCast::class])
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($notifications);
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
                    $historyObjects = array_merge(
                        $historyObjects,
                        $this->changeService->historyForFrontend($project)
                    );
                }
            }

            if (request('historyType') === 'event') {
                $event = Event::find(request('modelId'));
                if ($event !== null) {
                    $historyObjects = array_merge(
                        $historyObjects,
                        $this->changeService->historyForFrontend($event)
                    );
                }
            }

            if (request('historyType') === 'vacations') {
                $vacations = $this->vacationService->findVacationsByUserId(request('modelId'));

                foreach ($vacations as $vacation) {
                    $historyObjects = array_merge(
                        $historyObjects,
                        $this->changeService->historyForFrontend($vacation)
                    );
                }
            }
        }

        if (request('openDeclineEvent')) {
            $event = Event::find(request('eventId'));
        }

        if (request('openEditEvent')) {
            $event = Event::with([
                'room',
                'creator',
                'project',
                'project.managerUsers',
                'project.status',
                'event_type',
                'eventStatus',
                'eventProperties',
                'shifts',
                'shifts.craft',
                'shifts.users',
                'shifts.freelancer',
                'shifts.serviceProvider',
                'shifts.shiftsQualifications',
                'subEvents.event',
                'subEvents.event.room',
                'series',
            ])->find(request('eventId'));
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
            'project' => null,
            'wantedSplit' => $event?->room_id,
            'roomCollisions' => [],
            'notifications' => $output,
            'readNotifications' => $outputRead,
            'globalNotification' => $globalNotificationService->getGlobalNotificationEnrichedByImageUrl(),
            'rooms' => RoomIndexWithoutEventsResource::collection(Room::all())->resolve(),
            'eventTypes' => EventTypeResource::collection(EventType::all())->resolve(),
            'projects' => NotificationProjectResource::collection(
                Project::select([
                    'id', 'name', 'shift_description',
                    'number_of_participants', 'is_group', 'key_visual_path', 'cost_center_id'
                ])->with(['groups', 'sectors', 'categories', 'genres', 'costCenter'])->get()
            )->resolve(),
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

        if (count(array_diff($wantedNotification->getAttribute('data')['buttons'], ['showInTasks', 'show_project', 'delete_shift_notification', 'see_shift', 'change_shift', 'accept', 'decline', 'answerDialog', 'answer', 'change_request', 'event_delete'])) > 0) {
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
            if (count(array_diff($notification->data['buttons'], ['showInTasks', 'show_project', 'delete_shift_notification', 'see_shift', 'change_shift', 'accept', 'decline', 'answerDialog', 'answer', 'change_request', 'event_delete'])) > 0) {
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
        if ($user === null) {
            return 'User not found';
        }
        $notification = $user->notifications->find($id);
        $notification?->delete();
        return 'Notification deleted';
    }
}

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
use Artwork\Modules\Notification\Jobs\ArchiveUserNotificationsJob;
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
    /**
     * Above this number of unread notifications the "archive all" operation is offloaded to a
     * queued job instead of running inline in the request.
     */
    private const INLINE_ARCHIVE_THRESHOLD = 500;

    public function __construct(
        private readonly VacationService $vacationService,
        private readonly ChangeService $changeService,
    ) {
    }

    /**
     * Returns the unread/archived notification counts per group for the given user as a single
     * aggregated query (no row hydration), keyed by every NotificationGroupEnum value.
     *
     * @return array<string, array{unread: int, archived: int}>
     */
    private function getNotificationCountsByGroup(User $user): array
    {
        $counts = [];
        foreach (NotificationGroupEnum::cases() as $case) {
            $counts[$case->value] = ['unread' => 0, 'archived' => 0];
        }

        $rows = $user->notifications()
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.groupType')) as group_type")
            ->selectRaw('SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread')
            ->selectRaw('SUM(CASE WHEN read_at IS NOT NULL THEN 1 ELSE 0 END) as archived')
            ->groupBy('group_type')
            ->get();

        foreach ($rows as $row) {
            if ($row->group_type === null || !isset($counts[$row->group_type])) {
                continue;
            }

            $counts[$row->group_type] = [
                'unread' => (int) $row->unread,
                'archived' => (int) $row->archived,
            ];
        }

        return $counts;
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

        return inertia('Notifications/Show', [
            'historyObjects' => $historyObjects,
            'event' => $event !== null ? new CalendarEventResource($event) : null,
            'project' => null,
            'wantedSplit' => $event?->room_id,
            'roomCollisions' => [],
            'notificationCounts' => $this->getNotificationCountsByGroup($user),
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

    /**
     * Paginated notifications for a single group + read-state. Loaded lazily per section so a user
     * with thousands of notifications never ships the whole list to the browser at once.
     */
    public function list(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'groupType' => ['required', 'string'],
            'status' => ['nullable', 'in:unread,archived'],
            'perPage' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer'],
        ]);

        $perPage = min(50, max(1, (int) ($validated['perPage'] ?? 20)));
        $status = $validated['status'] ?? 'unread';

        $query = Auth::user()
            ->notifications()
            ->select(['id', 'type', 'data', 'read_at', 'created_at'])
            ->where('data->groupType', $validated['groupType'])
            ->orderBy('created_at', 'desc');

        $status === 'archived' ? $query->whereNotNull('read_at') : $query->whereNull('read_at');

        return response()->json($query->paginate($perPage));
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

        if (!$databaseNotificationService->isArchivable($wantedNotification)) {
            return;
        }

        $wantedNotification->setAttribute('read_at', $carbonService->getNow());
        $wantedNotification->save();
    }

    /**
     * Archives all archivable unread notifications of the current user, optionally restricted to a
     * single group. Small sets are archived inline (chunked bulk update); large sets are offloaded
     * to a queued job so the request returns immediately and the server is not blocked.
     */
    public function setOnReadAll(Request $request, DatabaseNotificationService $databaseNotificationService): void
    {
        $user = User::find(Auth::id());

        if ($user === null) {
            return;
        }

        $groupType = $request->filled('groupType') ? $request->string('groupType')->toString() : null;

        $unreadQuery = $user->notifications()->whereNull('read_at');
        if ($groupType !== null) {
            $unreadQuery->where('data->groupType', $groupType);
        }

        if ($unreadQuery->count() > self::INLINE_ARCHIVE_THRESHOLD) {
            ArchiveUserNotificationsJob::dispatch($user->id, $groupType);
            return;
        }

        $databaseNotificationService->archiveAllUnreadForUser($user, $groupType);
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

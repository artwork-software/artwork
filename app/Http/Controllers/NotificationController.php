<?php

namespace App\Http\Controllers;

use Artwork\Core\Carbon\Service\CarbonService;
use Artwork\Core\Casts\TimeAgoCast;
use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\Event\Models\EventStatus;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\EventType\Http\Resources\EventTypeResource;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\GlobalNotification\Services\GlobalNotificationService;
use Artwork\Modules\Notification\Jobs\ArchiveUserNotificationsJob;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Services\ProjectTabService;
use Artwork\Modules\Room\Http\Resources\RoomIndexWithoutEventsResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Illuminate\Http\Request;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\Notification\Services\NotificationDialogDataService;
use Artwork\Modules\Event\Services\EventPropertyService;
use Artwork\Modules\Notification\Services\NotificationSettingsPresenter;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

        // reorder(): die notifications()-Relation sortiert per latest() nach created_at; mit GROUP BY
        // verweigert MySQL 8 (ONLY_FULL_GROUP_BY) diese ORDER BY, MariaDB nicht.
        $rows = $user->notifications()
            ->reorder()
            // groupType ist eine generierte Spalte aus data (Index Empfänger/Gruppe/gelesen)
            ->selectRaw('groupType as group_type')
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
        UserService $userService,
        NotificationDialogDataService $notificationDialogDataService,
        EventPropertyService $eventPropertyService
    ): Response|ResponseFactory {
        $userService->updateCurrentUserShowNotificationIndicator(
            $userService->getAuthUser(),
            false
        );

        /** @var User $user */
        $user = Auth::user();
        // Dialoge der Benachrichtigungen (Absagen, Bearbeiten/Annehmen, Antworten, Verlauf) –
        // gemeinsam mit dem Dashboard, IDs aus der URL werden dort autorisiert
        $dialogData = $notificationDialogDataService->forRequest(request(), $user);

        return inertia('Notifications/Show', [
            'historyObjects' => $dialogData['historyObjects'],
            'event' => $dialogData['event'],
            'project' => null,
            'wantedSplit' => $dialogData['wantedSplit'],
            // ohne Eigenschaften schickte der Bearbeiten-Dialog event_properties: [] → sync([]) löschte sie
            'event_properties' => $eventPropertyService->getAll(),
            'roomCollisions' => [],
            'notificationCounts' => $this->getNotificationCountsByGroup($user),
            'globalNotification' => $globalNotificationService->getGlobalNotificationEnrichedByImageUrl(),
            'rooms' => RoomIndexWithoutEventsResource::collection(Room::all())->resolve(),
            'eventTypes' => EventTypeResource::collection(EventType::query()->with('verifiers')->get())->resolve(),
            // Die Antwort-Modals zeigen nur den Projektnamen; vorher gingen alle Projekte mit
            // Gruppen, Sektoren, Kategorien, Genres und Kostenstelle mit (MB bei großen Häusern).
            'projects' => Project::query()->select(['id', 'name'])->get(),
            // Einstellungen: nur relevante Typen, Texte aus dem Enum (NotificationSettingsPresenter)
            'notificationSettingGroups' => app(NotificationSettingsPresenter::class)->groupsFor($user),
            'notificationFrequencies' => array_map(fn (NotificationFrequencyEnum $frequency) => [
                'title' => $frequency->title(),
                'value' => $frequency->value,
            ], NotificationFrequencyEnum::cases()),
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
            ->where('groupType', $validated['groupType'])
            ->orderBy('created_at', 'desc');

        $status === 'archived' ? $query->whereNotNull('read_at') : $query->whereNull('read_at');

        return response()->json($query->paginate($perPage));
    }

    public function setReadAt(
        Request $request,
        DatabaseNotificationService $databaseNotificationService,
        CarbonService $carbonService
    ): void {
        /** @var DatabaseNotification|null $wantedNotification */
        $wantedNotification = $request->user()?->notifications()->find($request->string('notificationId'));

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
    public function setOnReadAll(
        Request $request,
        DatabaseNotificationService $databaseNotificationService
    ): JsonResponse {
        $user = User::find(Auth::id());

        if ($user === null) {
            return response()->json(['archived' => 0, 'remaining' => 0, 'queued' => false]);
        }

        $groupType = $request->filled('groupType') ? $request->string('groupType')->toString() : null;

        $unreadQuery = $user->notifications()->whereNull('read_at');
        if ($groupType !== null) {
            $unreadQuery->where('groupType', $groupType);
        }

        if ($unreadQuery->count() > self::INLINE_ARCHIVE_THRESHOLD) {
            ArchiveUserNotificationsJob::dispatch($user->id, $groupType);

            return response()->json(['archived' => 0, 'remaining' => 0, 'queued' => true]);
        }

        $archived = $databaseNotificationService->archiveAllUnreadForUser($user, $groupType);

        // Rückmeldung fürs Center: was archiviert wurde und was noch eine Aktion braucht
        return response()->json([
            'archived' => $archived,
            'remaining' => (clone $unreadQuery)->count(),
            'queued' => false,
        ]);
    }

    public function updateSetting(Request $request, NotificationSetting $setting): JsonResponse|RedirectResponse
    {
        if (Auth::id() !== $setting->user_id) {
            abort(403);
        }

        // vorher ungeprüft: ungültige Häufigkeit → 500, "false" als Text → an
        $validated = $request->validate([
            'enabled_email' => ['sometimes', 'boolean'],
            'enabled_push' => ['sometimes', 'boolean'],
            'frequency' => ['sometimes', Rule::enum(NotificationFrequencyEnum::class)],
        ]);
        $settingService = app(NotificationSettingService::class);
        $immediateBefore = $settingService->immediateMailTypes(Auth::user());
        $setting->update($validated);
        $settingService->summariseTypesNoLongerImmediate(Auth::user(), $immediateBefore);

        return $request->expectsJson() && !$request->header('X-Inertia')
            ? response()->json(['setting' => $setting->fresh()])
            : back();
    }

    /**
     * Sammeländerung für eine Gruppe oder alle sichtbaren Typen (E-Mail, Hinweis, Häufigkeit).
     */
    public function bulkUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'groupType' => ['nullable', Rule::enum(NotificationGroupEnum::class)],
            'enabled_email' => ['sometimes', 'boolean'],
            'enabled_push' => ['sometimes', 'boolean'],
            'frequency' => ['sometimes', Rule::enum(NotificationFrequencyEnum::class)],
        ]);
        $values = array_intersect_key($validated, array_flip(['enabled_email', 'enabled_push', 'frequency']));
        foreach (['enabled_email', 'enabled_push'] as $flag) {
            if (array_key_exists($flag, $values)) {
                $values[$flag] = (bool) $values[$flag];
            }
        }

        $user = Auth::user();
        $settingService = app(NotificationSettingService::class);
        $immediateBefore = $settingService->immediateMailTypes($user);
        $user->notificationSettings()
            ->whereIn('type', app(NotificationSettingsPresenter::class)->visibleTypeValuesFor($user))
            ->when(
                $validated['groupType'] ?? null,
                static fn ($query, string $groupType) => $query->where('group_type', $groupType)
            )
            ->update($values);
        $settingService->summariseTypesNoLongerImmediate($user, $immediateBefore);

        return $this->settingsResponse($request, $user);
    }

    /**
     * Alle Einstellungen auf die Standardwerte (E-Mail und Hinweis an, Häufigkeit je Typ).
     */
    public function resetSettings(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $settingService = app(NotificationSettingService::class);
        $immediateBefore = $settingService->immediateMailTypes($user);
        foreach (NotificationEnum::configurableCases() as $type) {
            $user->notificationSettings()->where('type', $type->value)->update([
                'enabled_email' => true,
                'enabled_push' => true,
                'frequency' => $type->defaultFrequency()->value,
            ]);
        }
        $settingService->summariseTypesNoLongerImmediate($user, $immediateBefore);

        return $this->settingsResponse($request, $user);
    }

    private function settingsResponse(Request $request, User $user): JsonResponse|RedirectResponse
    {
        return $request->expectsJson() && !$request->header('X-Inertia')
            ? response()->json(['groups' => app(NotificationSettingsPresenter::class)->groupsFor($user)])
            : back();
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

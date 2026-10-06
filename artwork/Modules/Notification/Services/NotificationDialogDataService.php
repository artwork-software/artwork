<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Change\Services\ChangeService;
use Artwork\Modules\Event\Http\Resources\CalendarEventResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventComment;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\Vacation\Services\VacationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Daten für die Dialoge, die eine Benachrichtigung per router.reload nachlädt (Belegung absagen,
 * Bearbeiten/Annehmen, Antworten, Verlauf). Eine Stelle für Benachrichtigungsseite UND Dashboard –
 * vorher zwei Kopien, von denen das Dashboard „Absagen“ und den Abwesenheitsverlauf nicht kannte
 * und den Termin in {data: …} verpackt lieferte. Jede ID kommt aus der URL und wird geprüft.
 */
class NotificationDialogDataService
{
    private const EDIT_RELATIONS = [
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
    ];

    public function __construct(
        private readonly ChangeService $changeService,
        private readonly VacationService $vacationService,
    ) {
    }

    /**
     * @return array{event: array<string, mixed>|null, historyObjects: array<int, mixed>, wantedSplit: int|null}
     */
    public function forRequest(Request $request, User $viewer): array
    {
        $event = null;
        if ($request->boolean('openEditEvent')) {
            $event = Event::with(self::EDIT_RELATIONS)->find($request->integer('eventId'));
        } elseif ($request->boolean('openDeclineEvent')) {
            $event = Event::with(['room', 'creator', 'project', 'event_type'])->find($request->integer('eventId'));
        }

        if ($event !== null && !Gate::forUser($viewer)->allows('view', $event)) {
            $event = null;
        }

        return [
            'event' => $event !== null ? $this->eventPayload($event, $viewer) : null,
            'historyObjects' => $request->boolean('showHistory')
                ? $this->history((string) $request->input('historyType'), $request->integer('modelId'), $viewer)
                : [],
            'wantedSplit' => $event?->room_id,
        ];
    }

    /**
     * Ungewrappt (resolve) plus das, was der Antwort-Dialog braucht: Bearbeiten-Recht und Verlauf
     * der Rückfragen – ohne beides war „Antwort senden“ dauerhaft gesperrt.
     *
     * @return array<string, mixed>
     */
    private function eventPayload(Event $event, User $viewer): array
    {
        $gate = Gate::forUser($viewer);
        $canEdit = $gate->allows('update', $event);
        // Rückfragen zwischen Raumadmin und Anfragender: nur für Beteiligte – sehen darf den Termin
        // (EventPolicy::view) sonst jede eingeloggte Person
        $mayReadComments = $canEdit
            || $event->user_id === $viewer->id
            || $gate->allows('answerRoomRequest', $event)
            || $gate->allows('declineEvent', $event);

        return (new CalendarEventResource($event))->resolve() + [
            'canEdit' => $canEdit,
            'comments' => !$mayReadComments ? [] : $event->comments()->with('user')->get()
                ->map(fn (EventComment $comment): array => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at?->translatedFormat('d.m.Y H:i'),
                    'user' => $comment->user?->only(['id', 'first_name', 'last_name', 'profile_photo_url']),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function history(string $historyType, int $modelId, User $viewer): array
    {
        $gate = Gate::forUser($viewer);

        if ($historyType === 'project') {
            $project = Project::find($modelId);

            return $project !== null && $gate->allows('view', $project)
                ? $this->changeService->historyForFrontend($project)
                : [];
        }

        if ($historyType === 'event') {
            $event = Event::find($modelId);

            return $event !== null && $gate->allows('view', $event)
                ? $this->changeService->historyForFrontend($event)
                : [];
        }

        if ($historyType === 'vacations' && $this->mayViewAbsences($viewer, $modelId)) {
            // Änderungen werden am Abwesenheits- UND am Verfügbarkeitsmodell protokolliert
            // (beide melden VACATION_CHANGES); vorher fehlten die Verfügbarkeiten → leeres Modal
            $models = $this->vacationService->findVacationsByUserId($modelId)
                ->concat(
                    Availability::query()
                        ->where('available_type', (new User())->getMorphClass())
                        ->where('available_id', $modelId)
                        ->get()
                );

            $history = [];
            foreach ($models as $model) {
                $history = array_merge($history, $this->changeService->historyForFrontend($model));
            }

            return $history;
        }

        return [];
    }

    private function mayViewAbsences(User $viewer, int $userId): bool
    {
        return $viewer->id === $userId
            || $viewer->can(PermissionEnum::SHIFT_PLANNER->value)
            || $viewer->can(PermissionEnum::AVAILABILITY_MANAGEMENT->value);
    }
}

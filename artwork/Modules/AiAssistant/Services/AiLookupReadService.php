<?php

namespace Artwork\Modules\AiAssistant\Services;

use Artwork\Modules\AiAssistant\DTOs\AiChecklistData;
use Artwork\Modules\AiAssistant\DTOs\AiChecklistListData;
use Artwork\Modules\AiAssistant\DTOs\AiEventTypeData;
use Artwork\Modules\AiAssistant\DTOs\AiEventTypeListData;
use Artwork\Modules\AiAssistant\DTOs\AiProjectStateListData;
use Artwork\Modules\AiAssistant\DTOs\AiQualificationListData;
use Artwork\Modules\AiAssistant\DTOs\AiRefData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomData;
use Artwork\Modules\AiAssistant\DTOs\AiRoomListData;
use Artwork\Modules\AiAssistant\Support\AiText;
use Artwork\Modules\AiAssistant\Support\WebGuardScope;
use Artwork\Modules\Checklist\Models\Checklist;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectState;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Raeume und Terminarten nachschlagen (core-api-lookups; Konzept B1 §3, W1 §1).
 *
 * Zweck ist das Aufloesen von Namen in IDs: `create_events` und `update_event` verlangen
 * `room_id` und `type_id`, und bis hierher hatte das Modell keine Liste, aus der es sie nehmen
 * konnte (Fund aus `ai-write-tools-confirm`).
 *
 * Gesucht wird mit `LIKE` statt mit dem im Brief genannten `ILIKE`: der Core laeuft auf MySQL, wo
 * es `ILIKE` nicht gibt und der Vergleich ueber die Collation ohnehin unabhaengig von
 * Gross-/Kleinschreibung ist.
 */
class AiLookupReadService
{
    public function rooms(User $user, ?string $search, ?int $limit): AiRoomListData
    {
        $limit = $this->limit($limit);

        $query = Room::query()
            ->visibleFor($user)
            ->when(
                $search !== null,
                fn (Builder $builder) => $builder->where('name', 'like', $this->contains((string) $search))
            );

        $total = (clone $query)->count();

        $rooms = $query
            ->with('area')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return new AiRoomListData(
            data: $rooms->map(static fn (Room $room): AiRoomData => new AiRoomData(
                id: $room->id,
                name: AiText::truncate($room->name),
                area: AiRefData::make($room->area_id, $room->area?->name),
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * Terminarten mit genau einer Default-Markierung.
     *
     * Der Default ist die Zeile mit `fallback_type` — dieselbe Kennzeichnung, die die Oberflaeche
     * benutzt; gibt es keine, ist es die alphabetisch erste. Bestimmt wird er ueber den
     * **gesamten** Bestand, nicht ueber die ausgelieferte Seite, und dieselbe Sortierung stellt
     * ihn an die erste Stelle. Schraenkt `q` die Liste ein, kann der Default herausfallen — dann
     * traegt die Antwort keinen, was richtig ist: sie ist dann keine vollstaendige Auswahl.
     */
    public function eventTypes(?string $search, ?int $limit): AiEventTypeListData
    {
        $limit = $this->limit($limit);

        $query = EventType::query()
            ->when(
                $search !== null,
                fn (Builder $builder) => $builder->where('name', 'like', $this->contains((string) $search))
            );

        $total = (clone $query)->count();

        $defaultId = $this->sorted(EventType::query())->value('id');

        $types = $this->sorted($query)
            ->limit($limit)
            ->get();

        return new AiEventTypeListData(
            data: $types->map(static fn (EventType $type): AiEventTypeData => new AiEventTypeData(
                id: $type->id,
                name: AiText::truncate($type->name),
                abbreviation: AiText::truncate($type->abbreviation),
                is_default: $type->id === $defaultId,
            ))->all(),
            total: $total,
            limit: $limit,
            truncated: $total > $limit,
        );
    }

    /**
     * Checklisten, in die die Person Aufgaben legen kann (ai-scenarios-4 S10, Lookup).
     *
     * Vorauswahl: eigene Listen, mit der Person geteilte, Listen mit einer ihr zugewiesenen Aufgabe und
     * Listen in Projekten, die sie sieht (`Project::visibleFor`); danach `ChecklistPolicy::view` im
     * Guard `web` (inkl. Tab-Sichtbarkeit). Fremde private Listen anderer Personen ohne Projekt
     * erscheinen damit auch fuer Inhaber:innen globaler Checklisten-Rechte nicht — das Lookup dient dem
     * Anlegen eigener Aufgaben.
     */
    public function checklists(User $user, ?int $projectId, ?string $search, ?int $limit): AiChecklistListData
    {
        $limit = $this->limit($limit);

        $candidates = Checklist::query()
            ->where(static function (Builder $access) use ($user): void {
                $access->where('user_id', $user->id)
                    ->orWhereHas('users', static fn (Builder $users) => $users->where('users.id', $user->id))
                    ->orWhereHas(
                        'tasks.task_users',
                        static fn (Builder $users) => $users->where('users.id', $user->id)
                    )
                    ->orWhereIn('project_id', Project::query()->visibleFor($user)->select('projects.id'));
            })
            ->when($projectId !== null, static fn (Builder $builder) => $builder->where('project_id', $projectId))
            ->when(
                $search !== null,
                fn (Builder $builder) => $builder->where('name', 'like', $this->contains((string) $search))
            )
            ->with(['project', 'users', 'tasks.task_users'])
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->filter(static fn (Checklist $checklist): bool => WebGuardScope::run(
                static fn (): bool => Gate::forUser($user)->allows('view', $checklist)
            ))
            ->values();

        return new AiChecklistListData(
            data: $candidates->take($limit)->map(static fn (Checklist $checklist) => new AiChecklistData(
                id: (int) $checklist->id,
                name: AiText::truncate($checklist->name),
                project: AiRefData::make($checklist->project_id, $checklist->project?->name),
                private: (bool) $checklist->private,
            ))->all(),
            total: $candidates->count(),
            limit: $limit,
            truncated: $candidates->count() > $limit,
        );
    }

    /**
     * Funktionen (Schicht-Qualifikationen) nachschlagen (ai-scenarios-4 S13).
     */
    public function qualifications(?string $search, ?int $limit): AiQualificationListData
    {
        [$data, $total, $limit] = $this->named(ShiftQualification::query(), $search, $limit);

        return new AiQualificationListData($data, $total, $limit, $total > $limit);
    }

    /**
     * Projektstatus nachschlagen (ai-scenarios-4 S18).
     */
    public function projectStates(?string $search, ?int $limit): AiProjectStateListData
    {
        [$data, $total, $limit] = $this->named(ProjectState::query(), $search, $limit);

        return new AiProjectStateListData($data, $total, $limit, $total > $limit);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query
     * @return array{0: list<AiRefData>, 1: int, 2: int}
     */
    private function named(Builder $query, ?string $search, ?int $limit): array
    {
        $limit = $this->limit($limit);
        $query->when(
            $search !== null,
            fn (Builder $builder) => $builder->where('name', 'like', $this->contains((string) $search))
        );
        $total = (clone $query)->count();

        $data = $query->orderBy('name')->orderBy('id')->limit($limit)->get(['id', 'name'])
            ->map(static fn (\Illuminate\Database\Eloquent\Model $row): AiRefData => new AiRefData(
                (int) $row->getKey(),
                AiText::truncate((string) $row->getAttribute('name'))
            ))
            ->values()
            ->all();

        return [$data, $total, $limit];
    }

    /**
     * @param Builder<EventType> $query
     * @return Builder<EventType>
     */
    private function sorted(Builder $query): Builder
    {
        return $query->orderByDesc('fallback_type')->orderBy('name');
    }

    /**
     * Suchmuster fuer `LIKE`: Platzhalter im Suchwort bleiben Text.
     */
    private function contains(string $value): string
    {
        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value) . '%';
    }

    private function limit(?int $requested): int
    {
        $max = (int) config('ai.list_limit');

        if ($requested === null || $requested < 1) {
            return $max;
        }

        return min($requested, $max);
    }
}

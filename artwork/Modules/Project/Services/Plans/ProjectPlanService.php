<?php

namespace Artwork\Modules\Project\Services\Plans;

use Artwork\Modules\Accommodation\Models\Accommodation;
use Artwork\Modules\ArtistResidency\Models\ArtistResidency;
use Artwork\Modules\Crm\Models\CrmContact;
use Artwork\Modules\Crm\Models\CrmPropertyGroup;
use Artwork\Modules\Crm\Services\CrmPropertyGroupService;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectCreateSettings;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectComponentVisibilityService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Daten für Veranstaltungsplan und Künstlerplan eines Projekts (Word-Export).
 *
 * Leere Angaben fallen weg, damit im Dokument keine leeren Überschriften entstehen. Team und
 * Künstleraufenthalte erscheinen nur, wenn die exportierende Person die jeweilige Komponente im
 * Projekt sehen darf; private Kontaktdaten und CRM-Eigenschaften folgen ihren Sichtbarkeitsregeln.
 *
 * @phpstan-type PlanVenue array{name: string, address: string|null}
 * @phpstan-type PlanTeamMember array{name: string, function: string|null, phone: string|null, email: string|null}
 * @phpstan-type PlanEntry array{time: string, title: string, description: string|null, place: string|null,
 *     address: string|null}
 * @phpstan-type PlanDay array{label: string, entries: list<PlanEntry>}
 * @phpstan-type PlanHotel array{name: string|null, address: string|null, phone: string|null,
 *     arrival: string|null, departure: string|null, nights: int|null, room_type: string|null}
 */
class ProjectPlanService
{
    /** Die Pläne werden immer auf Deutsch ausgegeben (Angebot), unabhängig von der Sprache der Person. */
    private const LOCALE = 'de';

    /** @var array<int, string>|null */
    private ?array $projectRoleNames = null;

    public function __construct(
        private readonly ProjectComponentVisibilityService $componentVisibilityService,
        private readonly CrmPropertyGroupService $crmPropertyGroupService,
    ) {
    }

    public function canSeeTeam(User $user): bool
    {
        return $this->componentVisibilityService->canSeeComponentTypeInProject(
            $user,
            [ProjectTabComponentEnum::PROJECT_TEAM->value]
        );
    }

    public function canSeeArtistResidencies(User $user): bool
    {
        return $this->componentVisibilityService->canSeeComponentTypeInProject(
            $user,
            [ProjectTabComponentEnum::ARTIST_RESIDENCIES->value]
        );
    }

    /**
     * Auswahl für den Export-Dialog: Spielstätten-Vorschlag, Aufenthalte, Termine und Terminarten.
     *
     * @return array<string, mixed>
     */
    public function options(Project $project, User $user): array
    {
        $events = $this->projectEvents($project, $user);
        $roomEventCounts = $events->whereNotNull('room_id')->countBy('room_id');

        $rooms = Room::query()
            ->without(['admins', 'creator'])
            ->orderBy('name')
            ->get(['id', 'name', 'street', 'zip_code', 'city'])
            ->map(fn (Room $room): array => [
                'id' => $room->id,
                'name' => $room->name,
                'address' => $room->formattedAddress(),
                'event_count' => (int) ($roomEventCounts[$room->id] ?? 0),
            ])
            // Räume des Projekts (meiste Termine zuerst) vor allen übrigen
            ->sortBy([['event_count', 'desc'], ['name', 'asc']])
            ->values();

        $eventTypeIdsInProject = $events->pluck('event_type_id')->filter()->unique()->values();
        $eventTypes = EventType::query()
            ->whereIn('id', $eventTypeIdsInProject)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rememberedTypeIds = collect((array) $user->artist_plan_event_type_ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->intersect($eventTypes->pluck('id'))
            ->values();

        return [
            'rooms' => $rooms,
            'suggested_venue_room_id' => $roomEventCounts->sortDesc()->keys()->first(),
            'event_types' => $eventTypes->map(fn (EventType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
            ])->values(),
            'remembered_event_type_ids' => $rememberedTypeIds,
            'events' => $events->map(fn (Event $event): array => [
                'id' => $event->id,
                'event_type_id' => $event->event_type_id,
                'day' => $this->dayLabel($event->start_time),
                'time' => $this->timeLabel($event),
                'title' => $this->eventTitle($event),
                'room' => $event->room?->name,
            ])->values(),
            'can_see_artist_residencies' => $this->canSeeArtistResidencies($user),
            'artist_residencies' => $this->canSeeArtistResidencies($user)
                ? $this->residencyOptions($project)
                : [],
        ];
    }

    /**
     * @return array{project_name: string, venue: PlanVenue|null, artists: list<string>,
     *     team: list<PlanTeamMember>, days: list<PlanDay>}
     */
    public function eventPlan(Project $project, User $user, ?Room $venue): array
    {
        return [
            'project_name' => (string) $project->name,
            'venue' => $this->venue($venue),
            'artists' => $this->artists($project),
            'team' => $this->canSeeTeam($user) ? $this->team($project, $user) : [],
            'days' => $this->days($this->projectEvents($project, $user), withAddress: false),
        ];
    }

    /**
     * @param array<int, int> $eventIds
     * @return array{project_name: string, artist_name: string|null, venue: PlanVenue|null,
     *     team: list<PlanTeamMember>, days: list<PlanDay>, hotel: PlanHotel|null}
     */
    public function artistPlan(
        Project $project,
        User $user,
        ?Room $venue,
        ?ArtistResidency $residency,
        array $eventIds
    ): array {
        $events = $this->projectEvents($project, $user)->whereIn('id', $eventIds)->values();

        return [
            'project_name' => (string) $project->name,
            'artist_name' => $this->filled($residency?->display_name),
            'venue' => $this->venue($venue),
            'team' => $this->canSeeTeam($user) ? $this->team($project, $user) : [],
            'days' => $this->days($events, withAddress: true),
            'hotel' => $residency !== null ? $this->hotel($residency, $user) : null,
        ];
    }

    /**
     * @param array<int, int> $eventTypeIds
     */
    public function rememberArtistPlanEventTypes(User $user, array $eventTypeIds): void
    {
        $user->forceFill([
            'artist_plan_event_type_ids' => array_values(array_unique(array_map('intval', $eventTypeIds))),
        ])->save();
    }

    /**
     * Termine des Projekts; Planungstermine nur für Personen, die den Planungskalender sehen dürfen.
     *
     * @return EloquentCollection<int, Event>
     */
    private function projectEvents(Project $project, User $user): EloquentCollection
    {
        return Event::query()
            ->where('project_id', $project->id)
            ->visiblePlanningFor($user)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->with([
                'event_type:id,name',
                'room' => fn ($rooms) => $rooms->without(['admins', 'creator'])
                    ->select(['id', 'name', 'street', 'zip_code', 'city']),
            ])
            ->orderBy('start_time')
            ->orderBy('id')
            ->get(['id', 'project_id', 'event_type_id', 'room_id', 'eventName', 'description', 'start_time',
                'end_time', 'allDay']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function residencyOptions(Project $project): array
    {
        return $project->artistResidencies()
            ->with(['accommodation', 'accommodationContact', 'artist', 'artistContact'])
            ->orderBy('arrival_date')
            ->orderBy('id')
            ->get()
            ->map(fn (ArtistResidency $residency): array => [
                'id' => $residency->id,
                'name' => $this->filled($residency->display_name),
                'accommodation' => $this->filled(
                    $residency->accommodationContact?->display_name ?? $residency->accommodation?->name
                ),
                'arrival' => $this->asDate($residency->arrival_date)?->format('d.m.Y'),
                'departure' => $this->asDate($residency->departure_date)?->format('d.m.Y'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return PlanVenue|null
     */
    private function venue(?Room $room): ?array
    {
        if ($room === null) {
            return null;
        }

        return ['name' => (string) $room->name, 'address' => $room->formattedAddress()];
    }

    /**
     * Künstler*innen als Liste: Freitext (je Zeile bzw. durch Komma/Semikolon getrennt), danach die
     * verknüpften Kontakte aus dem Adressbuch, die im Freitext noch nicht vorkommen.
     *
     * @return list<string>
     */
    private function artists(Project $project): array
    {
        $freeText = trim((string) $project->artists);
        $names = $freeText === ''
            ? []
            : array_values(array_filter(
                array_map('trim', preg_split('/[\r\n,;]+/u', $freeText) ?: []),
                static fn (string $name): bool => $name !== ''
            ));

        foreach ((array) $project->linked_artists as $linkedArtist) {
            $name = trim((string) ($linkedArtist['name'] ?? ''));
            if ($name === '' || mb_stripos($freeText, $name) !== false || in_array($name, $names, true)) {
                continue;
            }
            $names[] = $name;
        }

        return $names;
    }

    /**
     * Projektteam: Nutzer:innen (Projektleitung zuerst) und, wenn aktiviert, CRM-Kontakte im Team.
     *
     * @return list<PlanTeamMember>
     */
    private function team(Project $project, User $viewer): array
    {
        $users = $project->users()
            ->get()
            ->sortBy([
                fn (User $first, User $second): int => (int) (bool) $this->pivotValue($second, 'is_manager')
                    <=> (int) (bool) $this->pivotValue($first, 'is_manager'),
                fn (User $first, User $second): int => strcasecmp(
                    $first->last_name . ' ' . $first->first_name,
                    $second->last_name . ' ' . $second->first_name
                ),
            ])
            ->map(fn (User $user): array => [
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'function' => $this->filled($this->roleNames((array) $this->pivotValue($user, 'roles')))
                    ?? $this->filled($user->position),
                'phone' => $this->filled($user->visiblePhoneNumberFor($viewer)),
                'email' => $this->filled($user->visibleEmailFor($viewer)),
            ]);

        $contacts = collect();
        if (app(ProjectCreateSettings::class)->crm_contacts_in_team) {
            $visiblePropertyIds = $this->visibleCrmPropertyIds($viewer);
            $contacts = $project->teamCrmContacts()
                ->with('propertyValues.property')
                ->get()
                ->sortBy(fn (CrmContact $contact): string => mb_strtolower((string) $contact->display_name))
                ->map(fn (CrmContact $contact): array => [
                    'name' => (string) $contact->display_name,
                    'function' => $this->filled($this->roleNames((array) $this->pivotValue($contact, 'roles'))),
                    'phone' => $this->crmValue($contact, 'Telefon', $visiblePropertyIds),
                    'email' => $this->crmValue($contact, 'Email', $visiblePropertyIds),
                ]);
        }

        return $users->concat($contacts)
            ->filter(fn (array $member): bool => $member['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Event> $events
     * @return list<PlanDay>
     */
    private function days(Collection $events, bool $withAddress): array
    {
        return $events
            ->groupBy(fn (Event $event): string => $event->start_time->toDateString())
            ->map(fn (Collection $dayEvents): array => [
                'label' => $this->dayLabel($dayEvents->first()->start_time),
                'entries' => $dayEvents->map(fn (Event $event): array => [
                    'time' => $this->timeLabel($event),
                    'title' => $this->eventTitle($event),
                    'description' => $this->filled($event->description),
                    'place' => $this->filled($event->room?->name),
                    'address' => $withAddress ? $event->room?->formattedAddress() : null,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Unterkunft ohne Kosten, Tagegelder und Abzüge.
     *
     * @return PlanHotel|null
     */
    private function hotel(ArtistResidency $residency, User $viewer): ?array
    {
        $residency->loadMissing(['accommodation', 'accommodationContact.propertyValues.property', 'roomType']);

        $name = null;
        $address = null;
        $phone = null;

        $contact = $residency->accommodationContact;
        $accommodation = $contact?->getSourceEntity();
        if (!$accommodation instanceof Accommodation) {
            $accommodation = $contact === null ? $residency->accommodation : null;
        }

        if ($accommodation instanceof Accommodation) {
            $name = $this->filled($accommodation->name);
            $address = $this->joinAddress($accommodation->street, $accommodation->zip_code, $accommodation->location);
            $phone = $this->filled($accommodation->phone_number);
        } elseif ($contact !== null) {
            $visiblePropertyIds = $this->visibleCrmPropertyIds($viewer);
            $name = $this->filled($contact->display_name);
            $address = $this->joinAddress(
                $this->crmValue($contact, 'Straße, Hausnummer', $visiblePropertyIds),
                $this->crmValue($contact, 'PLZ', $visiblePropertyIds),
                $this->crmValue($contact, 'Stadt', $visiblePropertyIds),
            );
            $phone = $this->crmValue($contact, 'Telefon', $visiblePropertyIds);
        }

        $arrivalDate = $this->asDate($residency->arrival_date);
        $departureDate = $this->asDate($residency->departure_date);
        $nights = $residency->days !== null
            ? (int) $residency->days
            : ($arrivalDate !== null && $departureDate !== null
                ? (int) $arrivalDate->diffInDays($departureDate)
                : null);

        $hotel = [
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'arrival' => $this->dateTimeLabel($arrivalDate, $residency->arrival_time),
            'departure' => $this->dateTimeLabel($departureDate, $residency->departure_time),
            'nights' => $nights !== null && $nights > 0 ? $nights : null,
            'room_type' => $this->filled($residency->roomType?->name),
        ];

        return array_filter($hotel, static fn (mixed $value): bool => $value !== null) === [] ? null : $hotel;
    }

    private function eventTitle(Event $event): string
    {
        return $this->filled($event->eventName) ?? $this->filled($event->event_type?->name) ?? '';
    }

    private function dayLabel(CarbonInterface $date): string
    {
        return $date->copy()->locale(self::LOCALE)->translatedFormat('l, j. F Y');
    }

    /**
     * Uhrzeit des Termins; mehrtägige Termine nennen zusätzlich den Endtag.
     */
    private function timeLabel(Event $event): string
    {
        $start = $event->start_time->copy()->locale(self::LOCALE);
        $end = $event->end_time->copy()->locale(self::LOCALE);
        $sameDay = $start->isSameDay($end);

        if ($event->allDay) {
            return $sameDay ? 'ganztägig' : 'ganztägig bis ' . $end->translatedFormat('D, d.m.');
        }

        if ($sameDay) {
            return $start->format('H:i') === $end->format('H:i')
                ? $start->format('H:i')
                : $start->format('H:i') . ' – ' . $end->format('H:i');
        }

        return $start->format('H:i') . ' – ' . $end->translatedFormat('D, d.m.') . ' ' . $end->format('H:i');
    }

    private function dateTimeLabel(?CarbonInterface $date, ?string $time): ?string
    {
        if ($date === null) {
            return null;
        }

        $label = $date->copy()->locale(self::LOCALE)->translatedFormat('D, d.m.Y');
        $time = $this->filled($time);

        return $time !== null ? $label . ', ' . substr($time, 0, 5) . ' Uhr' : $label;
    }

    /**
     * @param array<int, mixed> $roleIds
     */
    private function roleNames(array $roleIds): string
    {
        $this->projectRoleNames ??= ProjectRole::query()->pluck('name', 'id')->all();

        return collect($roleIds)
            ->map(fn (mixed $roleId): ?string => $this->projectRoleNames[(int) $roleId] ?? null)
            ->filter()
            ->implode(', ');
    }

    /**
     * @return array<int, int>
     */
    private function visibleCrmPropertyIds(User $viewer): array
    {
        $departmentIds = $viewer->departments?->pluck('id')->all() ?? [];
        $groups = $this->crmPropertyGroupService->getVisibleForUser(
            $viewer->id,
            $departmentIds,
            $viewer->can(PermissionEnum::CRM_MANAGER->value)
        );
        $groups->loadMissing('properties');

        return $groups->flatMap(fn (CrmPropertyGroup $group) => $group->properties)->pluck('id')->map(
            fn (mixed $id): int => (int) $id
        )->all();
    }

    /**
     * @param array<int, int> $visiblePropertyIds
     */
    private function crmValue(CrmContact $contact, string $propertyName, array $visiblePropertyIds): ?string
    {
        $value = $contact->propertyValues->first(
            static fn ($propertyValue): bool => $propertyValue->property?->name === $propertyName
                && in_array((int) $propertyValue->crm_property_id, $visiblePropertyIds, true)
        )?->value;

        return $this->filled(is_scalar($value) ? (string) $value : null);
    }

    /**
     * Datumsfelder des Aufenthalts sind als date gecastet, im Docblock aber als string geführt.
     */
    private function asDate(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }

    /**
     * Wert aus der Pivot-Zeile einer belongsToMany-Relation (Projektteam).
     */
    private function pivotValue(Model $model, string $attribute): mixed
    {
        $pivot = $model->relationLoaded('pivot') ? $model->getRelation('pivot') : null;

        return $pivot instanceof Model ? $pivot->getAttribute($attribute) : null;
    }

    private function joinAddress(?string $street, ?string $zipCode, ?string $city): ?string
    {
        $cityLine = trim(($zipCode ?? '') . ' ' . ($city ?? ''));
        $parts = array_filter([trim($street ?? ''), $cityLine], static fn (string $part): bool => $part !== '');

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function filled(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

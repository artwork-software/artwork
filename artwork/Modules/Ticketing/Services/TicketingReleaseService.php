<?php

namespace Artwork\Modules\Ticketing\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Ticketing\Exceptions\TicketingConnectionException;
use Artwork\Modules\Ticketing\Models\TicketingConnection;
use Artwork\Modules\Ticketing\Models\TicketingEventRelease;
use Artwork\Modules\Ticketing\Models\TicketingRoomLink;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Preise, Plätze, Shop-Text und Ermäßigungen eines Termins festhalten und ihn in Artwork-Tickets
 * zum Verkauf stellen.
 *
 * Jede Aktion trifft eine ausdrückliche Liste von Terminen. Serien folgen der Regel des Kalenders:
 * wer ohne den Rest seiner Serie andere Preise bekommt, verlässt sie. Freigeben und Zurückziehen
 * ändern nichts am Termin und lassen die Serie deshalb unangetastet.
 */
class TicketingReleaseService
{
    /** @var array<string, array<string, mixed>>|null Spielstätten aus tickets, einmal je Anfrage geholt */
    private ?array $venues = null;

    public function __construct(
        private readonly TicketingConnectionService $connections,
        private readonly TicketingProductionService $productions,
        private readonly TicketsClient $tickets,
    ) {
    }

    /**
     * @param Collection<int, Event> $targets
     * @param array{
     *     classes: list<array{zone_key: string|null, name: string, price_cents: int, quota: int}>,
     *     description: string|null,
     *     reductions: list<array{id: string, offered: bool}>,
     * } $draft
     */
    public function saveDraft(Collection $targets, array $draft): void
    {
        DB::transaction(function () use ($targets, $draft): void {
            $this->leaveSeriesWhenPartial($targets);

            foreach ($targets as $target) {
                TicketingEventRelease::query()->updateOrCreate(['event_id' => $target->id], $draft);
            }
        });

        // Was schon verkauft wird, soll im Shop sofort stimmen.
        $connection = $this->connections->current();
        $released = $targets->filter(fn (Event $target): bool => $this->isReleased($target));

        if ($connection && $released->isNotEmpty()) {
            $productionId = $this->productionIdOf($released->first(), $connection);

            foreach ($released as $target) {
                $target = $target->fresh(['room', 'ticketingRelease']);
                $this->pushDate($connection, $productionId, $target);
                $target->ticketingRelease->update(['sync_error' => null]);
            }
        }
    }

    /** @param Collection<int, Event> $targets */
    public function release(Collection $targets, User $user): void
    {
        $connection = $this->requireConnection();
        $targets = $targets->reject(fn (Event $target): bool => $this->isReleased($target));

        if ($targets->isEmpty()) {
            return;
        }

        foreach ($targets as $target) {
            $this->assertReleasable($target);
        }

        $productionId = $this->productionIdOf($targets->first(), $connection);
        $pushed = [];

        // "Im Verkauf" erst, wenn alle Termine drüben sind und der Verkauf läuft; sonst gehen sie wieder raus.
        try {
            foreach ($targets as $target) {
                $pushed[$target->id] = $this->pushDate($connection, $productionId, $target)['id'];
            }

            $this->tickets->post($connection, "/productions/{$productionId}/publish");
        } catch (TicketingConnectionException $exception) {
            foreach ($pushed as $dateId) {
                // Das Aufräumen darf den eigentlichen Fehler nicht verdecken; was liegen bleibt, steht im Log.
                rescue(fn () => $this->tickets->delete($connection, "/dates/{$dateId}"));
            }

            throw $exception;
        }

        foreach ($targets as $target) {
            TicketingEventRelease::query()->updateOrCreate(
                ['event_id' => $target->id],
                [
                    'classes' => $target->ticketingRelease?->classes ?? $this->defaultClasses($target),
                    'state' => TicketingEventRelease::STATE_RELEASED,
                    'tickets_date_id' => $pushed[$target->id],
                    'released_at' => now(),
                    'released_by_user_id' => $user->id,
                    'sync_error' => null,
                ],
            );
        }
    }

    /** @param Collection<int, Event> $targets */
    public function withdraw(Collection $targets): void
    {
        $targets = $targets->filter(fn (Event $target): bool => $this->isReleased($target));

        if ($targets->isEmpty()) {
            return;
        }

        $connection = $this->requireConnection();

        foreach ($targets as $target) {
            $release = $target->ticketingRelease;

            if ($release->tickets_date_id) {
                $this->tickets->delete($connection, "/dates/{$release->tickets_date_id}");
            }

            $release->update([
                'state' => TicketingEventRelease::STATE_DRAFT,
                'tickets_date_id' => null,
                'released_at' => null,
                'released_by_user_id' => null,
                'sync_error' => null,
            ]);
        }
    }

    /** Ein geänderter Termin (Zeit, Raum) soll im Shop stimmen; ohne Freigabe gibt es nichts zu tun. */
    public function refresh(Event $event): void
    {
        if (!$this->isReleased($event)) {
            return;
        }

        $connection = $this->requireConnection();
        $this->pushDate($connection, $this->productionIdOf($event, $connection), $event);
        $event->ticketingRelease->update(['sync_error' => null]);
    }

    /**
     * @return array<string, mixed> released:false, oder der Stand aus tickets samt Gästeliste
     */
    public function sales(Event $event): array
    {
        $release = $event->ticketingRelease;
        $connection = $this->connections->current();

        if (!$connection || !$release || !$this->isReleased($event) || !$release->tickets_date_id) {
            return ['released' => false];
        }

        return ['released' => true] + $this->tickets->get($connection, "/dates/{$release->tickets_date_id}");
    }

    /**
     * Verkauft und Plätze der freigegebenen Termine im Zeitraum, für den Hinweis im Kalender:
     * ein Aufruf nach tickets für alle statt einer je Kachel. Nur Termine aus Projekten, die die
     * Person sehen darf — dieselbe Grenze wie bei den Ticketdetails.
     *
     * @return array<int, array{sold: int, capacity: int, cancelled: bool}> nach Termin-ID
     */
    public function calendarSummary(User $user, Carbon $start, Carbon $end): array
    {
        $connection = $this->connections->current();

        if (!$connection) {
            return [];
        }

        $visibleProjects = [];
        $events = Event::query()
            ->where('start_time', '<=', $end)
            ->where('end_time', '>=', $start)
            ->whereHas('ticketingRelease', static fn ($query) => $query
                ->where('state', TicketingEventRelease::STATE_RELEASED)
                ->whereNotNull('tickets_date_id'))
            ->with(['ticketingRelease', 'project'])
            ->get()
            ->filter(static function (Event $event) use ($user, &$visibleProjects): bool {
                if (!$event->project) {
                    return false;
                }

                return $visibleProjects[$event->project_id] ??= $user->can('view', $event->project);
            });

        return $this->summaryOf($connection, $events);
    }

    /**
     * Die freigegebenen unter den Terminen mit ihrem Verkaufsstand — für die Rückfrage vor dem Verschieben.
     * Der Stand ist Beiwerk: ist tickets nicht erreichbar, fehlt er, die Rückfrage bleibt.
     *
     * @param Collection<int, Event> $events
     * @return list<array{id: int, name: string, start: string, sold: int|null}>
     */
    public function onSale(Collection $events): array
    {
        $connection = $this->connections->current();
        $released = $events->filter(fn (Event $event): bool => $this->isReleased($event)
            && $event->ticketingRelease->tickets_date_id !== null);

        if (!$connection || $released->isEmpty()) {
            return [];
        }

        try {
            $summary = $this->summaryOf($connection, $released);
        } catch (TicketingConnectionException) {
            $summary = [];
        }

        return $released->map(static fn (Event $event): array => [
            'id' => $event->id,
            'name' => $event->eventName ?: ($event->project?->name ?? ''),
            'start' => $event->start_time->toIso8601String(),
            'sold' => $summary[$event->id]['sold'] ?? null,
        ])->values()->all();
    }

    /**
     * Ein Aufruf nach tickets je 500 Termine.
     *
     * @param Collection<int, Event> $events freigegeben, mit ticketingRelease
     * @return array<int, array{sold: int, capacity: int, cancelled: bool}> nach Termin-ID
     */
    private function summaryOf(TicketingConnection $connection, Collection $events): array
    {
        $eventIdsByDate = $events->mapWithKeys(
            static fn (Event $event): array => [$event->ticketingRelease->tickets_date_id => $event->id]
        );
        $summary = [];

        foreach ($eventIdsByDate->keys()->chunk(500) as $dateIds) {
            foreach ($this->tickets->get($connection, '/dates?ids=' . $dateIds->implode(',')) as $date) {
                $summary[$eventIdsByDate[$date['id']]] = [
                    'sold' => (int) $date['sold'],
                    'capacity' => (int) $date['capacity'],
                    'cancelled' => $date['status'] === 'cancelled',
                ];
            }
        }

        return $summary;
    }

    /**
     * Vorgabe der Spielstätte: jede Preisklasse mit ihren Plätzen und ihrem Standardpreis.
     *
     * @return list<array{zone_key: string, name: string, price_cents: int, quota: int}>
     */
    public function defaultClasses(Event $event): array
    {
        $venue = $this->venueOf($event);

        return array_map(static fn (array $zone): array => [
            'zone_key' => $zone['key'],
            'name' => $zone['name'],
            'price_cents' => (int) ($zone['defaultPriceCents'] ?? 0),
            'quota' => (int) ($zone['capacity'] ?? 0),
        ], $venue['zones'] ?? []);
    }

    public function isReleased(Event $event): bool
    {
        return $event->ticketingRelease?->state === TicketingEventRelease::STATE_RELEASED;
    }

    /**
     * Serienregel des Kalenders: bekommt ein Termin andere Preise als der Rest seiner Serie,
     * gehört er nicht mehr dazu. Ist die ganze Serie dabei, bleibt sie beisammen.
     *
     * @param Collection<int, Event> $targets
     */
    private function leaveSeriesWhenPartial(Collection $targets): void
    {
        $bySeries = $targets->filter(static fn (Event $target): bool => $target->is_series && $target->series_id)
            ->groupBy('series_id');

        foreach ($bySeries as $seriesId => $members) {
            $total = Event::query()->where('series_id', $seriesId)->where('is_series', true)->count();

            if ($members->count() < $total) {
                Event::query()->whereKey($members->pluck('id'))->update(['is_series' => false, 'series_id' => null]);
            }
        }
    }

    private function assertReleasable(Event $event): void
    {
        if (!$event->room_id || !TicketingRoomLink::query()->where('room_id', $event->room_id)->exists()) {
            throw new TicketingConnectionException(__(
                'The room of :event is not synced with Artwork-Tickets yet.',
                ['event' => $this->label($event)]
            ));
        }

        if (!$event->start_time) {
            throw new TicketingConnectionException(__(':event has no start time.', ['event' => $this->label($event)]));
        }

        if ($event->start_time->isPast()) {
            throw new TicketingConnectionException(__(
                ':event has already taken place and cannot be sold any more.',
                ['event' => $this->label($event)]
            ));
        }
    }

    /** Die Produktion des Projekts in tickets; angelegt aus dem Raum des ersten freigegebenen Termins. */
    private function productionIdOf(Event $event, TicketingConnection $connection): string
    {
        $venueId = $this->venueIdOf($event)
            ?? throw new TicketingConnectionException(__(
                'The room of :event is not synced with Artwork-Tickets yet.',
                ['event' => $this->label($event)]
            ));

        return $this->productions->ensure($event->project, $venueId, $connection)->production_id;
    }

    /** @return array<string, mixed> */
    private function pushDate(TicketingConnection $connection, string $productionId, Event $event): array
    {
        $release = $event->ticketingRelease;
        $classes = $release?->classes ?: $this->defaultClasses($event);

        return $this->tickets->put($connection, '/dates', [
            'externalRef' => (string) $event->id,
            'productionId' => $productionId,
            'venueId' => $this->venueIdOf($event),
            'startsAt' => $event->start_time->toIso8601String(),
            'endsAt' => $event->end_time?->toIso8601String(),
            'doorsAt' => $event->admission_time
                ? $event->start_time->copy()->setTimeFromTimeString($event->admission_time)->toIso8601String()
                : null,
            'capacity' => (int) array_sum(array_column($classes, 'quota')),
            'categories' => array_map(static fn (array $class): array => [
                'zoneKey' => $class['zone_key'],
                'name' => $class['name'],
                'priceCents' => (int) $class['price_cents'],
                'quota' => (int) $class['quota'],
            ], $classes),
            'description' => $release?->description,
            'reductions' => array_map(static fn (array $reduction): array => [
                'reductionTypeId' => $reduction['id'],
                'offered' => $reduction['offered'],
            ], $release?->reductions ?? []),
        ]);
    }

    private function venueIdOf(Event $event): ?string
    {
        return $event->room_id
            ? TicketingRoomLink::query()->where('room_id', $event->room_id)->value('venue_id')
            : null;
    }

    /** @return array<string, mixed>|null */
    private function venueOf(Event $event): ?array
    {
        $connection = $this->connections->current();
        $venueId = $this->venueIdOf($event);

        if (!$connection || !$venueId) {
            return null;
        }

        $this->venues ??= $this->connections->venues($connection);

        return $this->venues[$venueId] ?? null;
    }

    private function requireConnection(): TicketingConnection
    {
        return $this->connections->current()
            ?? throw new TicketingConnectionException(__('This installation is not connected to Artwork-Tickets yet.'));
    }

    private function label(Event $event): string
    {
        $name = $event->eventName ?: ($event->event_type?->name ?? __('Event'));

        return $name . ' · ' . $event->start_time?->format('d.m.Y H:i');
    }
}

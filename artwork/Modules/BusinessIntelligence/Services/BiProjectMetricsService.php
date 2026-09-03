<?php

namespace Artwork\Modules\BusinessIntelligence\Services;

use Artwork\Modules\BusinessIntelligence\Enums\BiPricingTypeEnum;
use Artwork\Modules\BusinessIntelligence\Enums\BiVisitorModeEnum;
use Artwork\Modules\BusinessIntelligence\Models\BiAudienceCategory;
use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\BusinessIntelligence\Models\BiProjectData;
use Artwork\Modules\Project\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes per-project BI key figures (visitors, sold tickets, revenue, capacity,
 * average price, occupancy). Single source of truth shared by export, dashboard
 * and project-list column so the numbers always agree.
 */
class BiProjectMetricsService
{
    /**
     * Datensatz-Scope: 'actual' (Ist, Default) oder 'plan'. Alle Getter lesen
     * die zum Scope passenden Relationen; forScope() liefert eine Plan-Sicht.
     */
    private string $scope = 'actual';

    /**
     * Alle Kategorien (inkl. deaktivierter/gelöschter — historische Werte zählen
     * weiter), einmal je Request memoisiert; wichtig für Dashboard-Schleifen.
     */
    private ?Collection $categoriesByIdCache = null;

    /**
     * Namen (klein geschrieben) der BI-Tags, die mindestens einer Terminart
     * zugeordnet sind. Steuert, ob die Tag-Kennzahlen (Vorstellungen,
     * Veranstaltungstage, Kapazität) überhaupt ermittelt werden können.
     * null = noch nicht geladen.
     *
     * @var array<string, true>|null
     */
    private ?array $linkedKpiTagNames = null;

    public const PERFORMANCE_TAG = 'Vorstellung';
    public const EVENT_DAY_TAG = 'Veranstaltungstag';

    public function forScope(string $scope): static
    {
        $clone = clone $this;
        $clone->scope = $scope;

        return $clone;
    }

    private function biDataOf(Project $project): ?BiProjectData
    {
        return $this->scope === 'plan' ? $project->planBiData : $project->biData;
    }

    private function eventDataOf(Project $project): Collection
    {
        $data = $this->scope === 'plan' ? $project->planBiEventData : $project->biEventData;

        return Collection::make($data ?? []);
    }

    /**
     * Plan-Ist-Gegenüberstellung der Kernkennzahlen. attainment = Ist/Plan in %.
     *
     * @return array<string, mixed>
     */
    public function planComparison(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $plan = $this->forScope('plan');

        $metrics = [];
        foreach (['visitors', 'sold_tickets', 'revenue', 'costs'] as $metric) {
            $planValue = match ($metric) {
                'visitors' => $plan->visitors($project, $from, $to),
                'sold_tickets' => $plan->soldTickets($project, $from, $to),
                'revenue' => $plan->revenue($project, $from, $to),
                'costs' => $plan->costs($project),
            };
            $actualValue = match ($metric) {
                'visitors' => $this->visitors($project, $from, $to),
                'sold_tickets' => $this->soldTickets($project, $from, $to),
                'revenue' => $this->revenue($project, $from, $to),
                'costs' => $this->costs($project),
            };

            $metrics[$metric] = [
                'plan' => $planValue,
                'actual' => $actualValue,
                'diff' => ($planValue !== null && $actualValue !== null)
                    ? round($actualValue - $planValue, 2)
                    : null,
                'attainment' => ($planValue !== null && $planValue > 0 && $actualValue !== null)
                    ? round($actualValue / $planValue * 100, 1)
                    : null,
            ];
        }

        // Ergebnis = Umsatz - Kosten, je Scope abgeleitet (nur wenn beide Werte da sind)
        $resultPlan = ($metrics['revenue']['plan'] !== null && $metrics['costs']['plan'] !== null)
            ? round($metrics['revenue']['plan'] - $metrics['costs']['plan'], 2)
            : null;
        $resultActual = ($metrics['revenue']['actual'] !== null && $metrics['costs']['actual'] !== null)
            ? round($metrics['revenue']['actual'] - $metrics['costs']['actual'], 2)
            : null;
        $metrics['result'] = [
            'plan' => $resultPlan,
            'actual' => $resultActual,
            'diff' => ($resultPlan !== null && $resultActual !== null)
                ? round($resultActual - $resultPlan, 2)
                : null,
            'attainment' => ($resultPlan !== null && $resultPlan > 0 && $resultActual !== null)
                ? round($resultActual / $resultPlan * 100, 1)
                : null,
        ];

        $hasPlan = collect($metrics)->contains(fn(array $entry): bool => $entry['plan'] !== null);

        return ['has_plan' => $hasPlan, 'metrics' => $metrics];
    }

    /**
     * Kosten des Projekts im aktuellen Scope. Nur als Projekt-Gesamtwert erfasst
     * (kein per-Event-Modus); null = nicht erfasst oder als nicht relevant markiert.
     */
    public function costs(Project $project): ?float
    {
        $biData = $this->biDataOf($project);

        if (!$biData || $biData->costs_not_applicable) {
            return null;
        }

        return $biData->costs_total !== null ? (float) $biData->costs_total : null;
    }

    /**
     * Kategorien vorab setzen (Tests / Aufrufer mit bereits geladener Liste).
     */
    public function primeCategories(Collection $categories): void
    {
        $this->categoriesByIdCache = $categories->keyBy('id');
    }

    /**
     * Verknüpfte KPI-Tags vorab setzen (Tests / Aufrufer ohne DB).
     *
     * @param array<int, string> $tagNames
     */
    public function primeLinkedKpiTags(array $tagNames): void
    {
        $this->linkedKpiTagNames = [];
        foreach ($tagNames as $name) {
            $this->linkedKpiTagNames[mb_strtolower($name)] = true;
        }
    }

    /**
     * Ist der BI-Tag (Vorstellung / Veranstaltungstag) mindestens einer
     * Terminart zugeordnet? Ohne Zuordnung gibt es bewusst KEINEN Fallback
     * auf "alle Termine" — die Kennzahl bleibt null, damit Proben und
     * Aufbauten nicht still als Vorstellungen gezählt werden.
     */
    public function kpiTagLinked(string $name): bool
    {
        if ($this->linkedKpiTagNames === null) {
            $this->linkedKpiTagNames = [];
            $tags = BiEventTypeTag::query()->has('eventTypes')->get(['name', 'name_de']);
            foreach ($tags as $tag) {
                foreach ([$tag->name, $tag->name_de] as $tagName) {
                    if ($tagName !== null && $tagName !== '') {
                        $this->linkedKpiTagNames[mb_strtolower($tagName)] = true;
                    }
                }
            }
        }

        return isset($this->linkedKpiTagNames[mb_strtolower($name)]);
    }

    /**
     * Full per-project KPI set for the project tab header.
     *
     * @return array<string, mixed>
     */
    public function summary(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $biData = $this->biDataOf($project);
        ['value' => $visitors, 'estimated' => $visitorsEstimated] = $this->visitorsWithEstimate($project, $from, $to);
        $soldTickets = $this->soldTickets($project, $from, $to);
        $revenue = $this->revenue($project, $from, $to);
        $capacity = $this->seatsCapacity($project, $from, $to);
        $performances = $this->performances($project, $from, $to);
        $eventDays = $this->eventDays($project, $from, $to);
        $categorySums = $this->categorySums($project, $from, $to);
        $ticketsIssued = $this->ticketsIssued($project, $from, $to);

        return [
            'visitors' => $visitors,
            'visitors_estimated' => $visitorsEstimated,
            'visitors_not_applicable' => (bool) $biData?->visitors_not_applicable,
            'sold_tickets' => $soldTickets,
            'sold_tickets_not_applicable' => (bool) $biData?->sold_tickets_not_applicable,
            'revenue' => $revenue !== null ? round($revenue, 2) : null,
            'revenue_not_applicable' => (bool) $biData?->revenue_not_applicable,
            'avg_price' => $this->averagePrice($revenue, $soldTickets),
            'capacity' => $capacity,
            'occupancy' => $this->occupancyRate($soldTickets, $capacity),
            'performances' => $performances,
            'event_days' => $eventDays,
            // false = Tag keiner Terminart zugeordnet → Kennzahl bleibt null (kein Fallback)
            'performance_tag_linked' => $this->kpiTagLinked(self::PERFORMANCE_TAG),
            'event_day_tag_linked' => $this->kpiTagLinked(self::EVENT_DAY_TAG),
            // Kategorien-Aufschlüsselung (null = für diese Rolle nichts erfasst)
            'category_sums' => $categorySums,
            'tickets_issued' => $ticketsIssued,
            'paid_tickets_from_categories' => $this->paidTicketsFromCategories($project, $from, $to),
            // Quoten-KPIs (null, wenn die Basis fehlt)
            'free_tickets_rate' => $this->freeTicketsRate($project, $from, $to),
            'reduced_tickets_rate' => $this->reducedTicketsRate($project, $from, $to),
            'paying_rate' => $this->payingRate($project, $from, $to),
            'no_show_rate' => $this->noShowRate($visitors, $ticketsIssued),
            // Platzauslastung: ausgegebene Karten inkl. Freikarten / Kapazität
            'seat_occupancy' => $this->occupancyRate($ticketsIssued, $capacity),
            'avg_revenue_per_visitor' => ($revenue !== null && $visitors)
                ? round($revenue / $visitors, 2)
                : null,
            'visitors_per_performance' => ($visitors !== null && $performances !== null && $performances > 0)
                ? round($visitors / $performances, 1)
                : null,
            'revenue_per_event_day' => ($revenue !== null && $eventDays !== null && $eventDays > 0)
                ? round($revenue / $eventDays, 2)
                : null,
        ];
    }

    /**
     * Summen je Rechenrolle (full/reduced/free) aus den Kategorie-Werten.
     * null je Rolle = für diese Rolle wurde nichts erfasst (unterscheidet 0 von "leer").
     *
     * @return array{full: ?int, reduced: ?int, free: ?int}
     */
    public function categorySums(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $sums = ['full' => null, 'reduced' => null, 'free' => null];
        $entries = $this->categoryEntries($project, $from, $to);

        if ($entries->isEmpty()) {
            return $sums;
        }

        $categories = $this->categoriesById();

        foreach ($entries as $entry) {
            $category = $categories->get($entry->bi_audience_category_id);

            if (!$category) {
                continue;
            }

            $type = $category->pricing_type->value;
            $sums[$type] = ($sums[$type] ?? 0) + (int) $entry->quantity;
        }

        return $sums;
    }

    /**
     * Ausgegebene Karten = Summe aller Kategorien (zahlend + frei);
     * null, wenn keinerlei Kategoriewerte erfasst sind.
     */
    public function ticketsIssued(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        $sums = $this->categorySums($project, $from, $to);
        $recorded = array_filter($sums, static fn(?int $value): bool => $value !== null);

        return $recorded === [] ? null : array_sum($recorded);
    }

    /**
     * Verkaufte Karten aus den zahlenden Kategorien (full + reduced);
     * null, wenn keine zahlende Kategorie erfasst ist.
     */
    public function paidTicketsFromCategories(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        $sums = $this->categorySums($project, $from, $to);

        if ($sums['full'] === null && $sums['reduced'] === null) {
            return null;
        }

        return (int) $sums['full'] + (int) $sums['reduced'];
    }

    /**
     * Summe je Kategorie-Id (für Export-Spalten und Aufschlüsselungs-Anzeigen);
     * enthält nur Kategorien mit erfassten Werten im Modus/Zeitraum.
     *
     * @return array<int, int>
     */
    public function categoryQuantities(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return $this->categoryEntries($project, $from, $to)
            ->groupBy('bi_audience_category_id')
            ->map(static fn(Collection $entries): int => (int) $entries->sum('quantity'))
            ->all();
    }

    public function freeTicketsRate(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        $sums = $this->categorySums($project, $from, $to);
        $issued = $this->ticketsIssued($project, $from, $to);

        if ($sums['free'] === null || !$issued) {
            return null;
        }

        return round($sums['free'] / $issued * 100, 1);
    }

    public function reducedTicketsRate(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        $sums = $this->categorySums($project, $from, $to);
        $paid = $this->paidTicketsFromCategories($project, $from, $to);

        if ($sums['reduced'] === null || !$paid) {
            return null;
        }

        return round($sums['reduced'] / $paid * 100, 1);
    }

    public function payingRate(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        $paid = $this->paidTicketsFromCategories($project, $from, $to);
        $issued = $this->ticketsIssued($project, $from, $to);

        if ($paid === null || !$issued) {
            return null;
        }

        return round($paid / $issued * 100, 1);
    }

    /**
     * No-Show-Quote in Prozent; kann negativ werden (mehr Besucher*innen als
     * ausgegebene Karten) — Anzeige-Entscheidung liegt beim Frontend.
     */
    public function noShowRate(?int $visitors, ?int $ticketsIssued): ?float
    {
        if ($visitors === null || !$ticketsIssued) {
            return null;
        }

        return round((1 - $visitors / $ticketsIssued) * 100, 1);
    }

    /**
     * Visitors with fallback: if no visitor figure was recorded but sold tickets
     * exist, use those as an estimate. Callers must surface the `estimated` flag
     * wherever the value is displayed.
     *
     * @return array{value: ?int, estimated: bool}
     */
    public function visitorsWithEstimate(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $visitors = $this->visitors($project, $from, $to);

        if ($visitors !== null) {
            return ['value' => $visitors, 'estimated' => false];
        }

        $biData = $this->biDataOf($project);

        if (!$biData || $biData->visitors_not_applicable || $biData->sold_tickets_not_applicable) {
            return ['value' => null, 'estimated' => false];
        }

        // Bevorzugt ausgegebene Karten (inkl. Freikarten — auch deren Gäste sind
        // Besucher*innen), erst dann verkaufte Karten.
        $ticketsIssued = $this->ticketsIssued($project, $from, $to);

        if ($ticketsIssued !== null) {
            return ['value' => $ticketsIssued, 'estimated' => true];
        }

        $soldTickets = $this->soldTickets($project, $from, $to);

        if ($soldTickets === null) {
            return ['value' => null, 'estimated' => false];
        }

        return ['value' => $soldTickets, 'estimated' => true];
    }

    /**
     * Events tagged "Vorstellung". null, solange der Tag keiner Terminart
     * zugeordnet ist (kein Fallback auf alle Termine — Produktentscheidung 03.09.2026).
     */
    public function performances(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        if (!$this->kpiTagLinked(self::PERFORMANCE_TAG)) {
            return null;
        }

        return $this->eventsInRange($project, $from, $to)
            ->filter(fn($event) => $this->eventHasTag($event, self::PERFORMANCE_TAG))
            ->count();
    }

    /**
     * Distinct days with events tagged "Veranstaltungstag"; null without tag link.
     */
    public function eventDays(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        if (!$this->kpiTagLinked(self::EVENT_DAY_TAG)) {
            return null;
        }

        return $this->eventsInRange($project, $from, $to)
            ->filter(fn($event) => $this->eventHasTag($event, self::EVENT_DAY_TAG))
            ->map(fn($event) => $event->start_time?->format('Y-m-d'))
            ->filter()
            ->unique()
            ->count();
    }

    public function visitors(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        $biData = $this->biDataOf($project);

        if (!$biData || $biData->visitors_not_applicable) {
            return null;
        }

        if ($biData->visitor_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->visitors_total;
        }

        $sum = $this->sumEventData($project, 'visitors', $from, $to);

        return $sum !== null ? (int) $sum : null;
    }

    public function soldTickets(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?int
    {
        $biData = $this->biDataOf($project);

        if ($biData?->sold_tickets_not_applicable) {
            return null;
        }

        $direct = null;

        if ($biData) {
            if ($biData->sold_tickets_mode === BiVisitorModeEnum::TOTAL) {
                $direct = $biData->sold_tickets_total;
            } else {
                $sum = $this->sumEventData($project, 'sold_tickets', $from, $to);
                $direct = $sum !== null ? (int) $sum : null;
            }
        }

        if ($direct !== null) {
            return $direct;
        }

        // Fallback: Summe der zahlenden Kategorien, wenn kein Direktwert erfasst ist.
        // Bei erfasstem Direktwert bleibt dieser führend (Abgleich-Warnung macht
        // Abweichungen zur Kategoriesumme im Frontend sichtbar).
        return $this->paidTicketsFromCategories($project, $from, $to);
    }

    public function revenue(Project $project, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        $biData = $this->biDataOf($project);

        if (!$biData || $biData->revenue_not_applicable) {
            return null;
        }

        if ($biData->revenue_mode === BiVisitorModeEnum::TOTAL) {
            return $biData->revenue_total !== null ? (float) $biData->revenue_total : null;
        }

        return $this->sumEventData($project, 'revenue', $from, $to);
    }

    public function averagePrice(?float $revenue, ?int $soldTickets): ?float
    {
        if ($revenue === null || !$soldTickets) {
            return null;
        }

        return round($revenue / $soldTickets, 2);
    }

    /**
     * Sum the available capacity per performance. A room used by multiple
     * performances contributes its capacity once for every performance.
     * 0 (→ Auslastung null), solange der Tag "Vorstellung" keiner Terminart
     * zugeordnet ist — sonst würden Proben und Aufbauten die Kapazität aufblähen.
     */
    public function seatsCapacity(Project $project, ?Carbon $from = null, ?Carbon $to = null): int
    {
        if (!$this->kpiTagLinked(self::PERFORMANCE_TAG)) {
            return 0;
        }

        $overrides = $project->biRoomCapacities->keyBy('room_id');

        if ($this->biDataOf($project)?->sold_tickets_mode === BiVisitorModeEnum::TOTAL) {
            $from = null;
            $to = null;
        }

        $events = ($from || $to) ? $this->eventsInRange($project, $from, $to) : $project->events;
        $relevantEvents = $events->filter(fn ($event) => $this->eventHasTag($event, self::PERFORMANCE_TAG));

        return (int) $relevantEvents
            ->filter(static fn ($event): bool => $event->room !== null)
            ->sum(function ($event) use ($overrides): int {
                $room = $event->room;
                $override = $overrides->get($room->id)?->capacity_override;

                return (int) ($override ?? $room->capacity ?? 0);
            });
    }

    public function occupancyRate(?int $soldTickets, int $capacity): ?float
    {
        if ($soldTickets === null || $capacity === 0) {
            return null;
        }

        return round($soldTickets / $capacity * 100, 1);
    }

    private function eventsInRange(Project $project, ?Carbon $from, ?Carbon $to)
    {
        return $project->events->filter(function ($event) use ($from, $to): bool {
            if (!$event->start_time) {
                return false;
            }

            if ($from && $event->end_time && $event->end_time->lt($from->copy()->startOfDay())) {
                return false;
            }

            if ($to && $event->start_time->gt($to->copy()->endOfDay())) {
                return false;
            }

            return true;
        });
    }

    /**
     * Kategorien-Werte (Ist-Scope) passend zum Erfassungsmodus der verkauften
     * Karten: TOTAL → Gesamtwerte (event_id null), PER_EVENT → Terminwerte im
     * Zeitraum. Erwartet die Relation biAudienceCategoryValues (geladen oder lazy).
     */
    private function categoryEntries(Project $project, ?Carbon $from, ?Carbon $to): Collection
    {
        $scope = $this->scope;
        $values = $project->biAudienceCategoryValues
            ->filter(static fn($value): bool => $value->scope === $scope && $value->quantity !== null);

        $mode = $this->biDataOf($project)?->sold_tickets_mode ?? BiVisitorModeEnum::TOTAL;

        if ($mode === BiVisitorModeEnum::TOTAL) {
            return $values->filter(static fn($value): bool => $value->event_id === null);
        }

        $eventsById = $project->events->keyBy('id');

        return $values->filter(function ($value) use ($eventsById, $from, $to): bool {
            if ($value->event_id === null) {
                return false;
            }

            $event = $eventsById->get($value->event_id);

            if (!$event) {
                return false;
            }

            if (($from || $to) && !$event->start_time) {
                return false;
            }

            if ($from && $event->start_time->lt($from->copy()->startOfDay())) {
                return false;
            }

            if ($to && $event->start_time->gt($to->copy()->endOfDay())) {
                return false;
            }

            return true;
        });
    }

    private function categoriesById(): Collection
    {
        return $this->categoriesByIdCache ??= BiAudienceCategory::withTrashed()->get()->keyBy('id');
    }

    private function eventHasTag($event, string $name): bool
    {
        $tags = $event->event_type?->biTags;

        if (!$tags) {
            return false;
        }

        return $tags->contains(
            fn($tag) => strcasecmp($tag->name_de ?? '', $name) === 0
                || strcasecmp($tag->name ?? '', $name) === 0
        );
    }

    /**
     * Sum of per-event values in range; null when no event has the field recorded
     * (distinguishes "nothing entered" from a genuine zero).
     */
    private function sumEventData(Project $project, string $field, ?Carbon $from, ?Carbon $to): ?float
    {
        $entries = $this->eventDataOf($project)
            ->filter(function ($eventData) use ($from, $to): bool {
                $event = $eventData->event;

                if (!$event) {
                    return false;
                }

                // start_time ist nullable; ohne Datumsbezug aus Datumsfiltern ausschließen
                // (sonst Null-Deref bei gesetztem $from/$to).
                if (($from || $to) && !$event->start_time) {
                    return false;
                }

                if ($from && $event->start_time->lt($from->copy()->startOfDay())) {
                    return false;
                }

                if ($to && $event->start_time->gt($to->copy()->endOfDay())) {
                    return false;
                }

                return true;
            })
            ->filter(fn($eventData) => $eventData->{$field} !== null);

        if ($entries->isEmpty()) {
            return null;
        }

        return (float) $entries->sum($field);
    }
}

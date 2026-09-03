<?php

namespace Artwork\Core\Console\Commands;

use Artwork\Modules\BusinessIntelligence\Models\BiEventTypeTag;
use Artwork\Modules\EventType\Models\EventType;
use Illuminate\Console\Command;

class AddBiEventTypeTagsCommand extends Command
{
    protected $signature = 'artwork:add-bi-event-type-tags';
    protected $description = 'Seed the default set of Business Intelligence event-type tags (idempotent)';

    /**
     * @var array<int, array{name: string, name_de: string, color: string, kpi_role: ?string}>
     */
    private const DEFAULT_TAGS = [
        ['name' => 'Event day', 'name_de' => 'Veranstaltungstag', 'color' => '#6366f1', 'kpi_role' => BiEventTypeTag::KPI_ROLE_EVENT_DAY],
        ['name' => 'Performance', 'name_de' => 'Vorstellung', 'color' => '#22c55e', 'kpi_role' => BiEventTypeTag::KPI_ROLE_PERFORMANCE],
        ['name' => 'Rehearsal', 'name_de' => 'Probe', 'color' => '#f59e0b', 'kpi_role' => null],
        ['name' => 'Education', 'name_de' => 'Vermittlung', 'color' => '#ec4899', 'kpi_role' => null],
        ['name' => 'Special event', 'name_de' => 'Sonderveranstaltung', 'color' => '#06b6d4', 'kpi_role' => null],
    ];

    /**
     * Terminarten-Namen (Teilstring, case-insensitive), die bei der ERSTEN Anlage
     * der KPI-Tags automatisch zugeordnet werden. Ohne Zuordnung bleiben
     * Vorstellungen/Veranstaltungstage/Auslastung bewusst leer (kein Fallback).
     *
     * @var array<string, array<int, string>>
     */
    private const KPI_ROLE_EVENT_TYPE_HINTS = [
        BiEventTypeTag::KPI_ROLE_PERFORMANCE => ['vorstellung', 'premiere', 'derniere', 'dernière', 'aufführung', 'auffuehrung', 'performance', 'konzert', 'show'],
        BiEventTypeTag::KPI_ROLE_EVENT_DAY => ['vorstellung', 'premiere', 'derniere', 'dernière', 'aufführung', 'auffuehrung', 'performance', 'konzert', 'show', 'veranstaltung'],
    ];

    public function handle(): void
    {
        foreach (self::DEFAULT_TAGS as $tag) {
            $existing = BiEventTypeTag::query()
                ->where('name', $tag['name'])
                ->orWhere('name_de', $tag['name_de'])
                ->first();

            if ($existing) {
                $this->info('BI event type tag "' . $tag['name_de'] . '" already exists');
                $this->assignRoleIfFree($existing, $tag['kpi_role']);
                continue;
            }

            // Rolle nur setzen, wenn sie nicht schon ein anderer Tag trägt (unique)
            $role = $tag['kpi_role'];
            if ($role !== null && BiEventTypeTag::query()->where('kpi_role', $role)->exists()) {
                $role = null;
            }

            $created = BiEventTypeTag::create([...$tag, 'kpi_role' => $role]);
            $this->info('BI event type tag "' . $tag['name_de'] . '" added');
            $this->linkEventTypesByName($created);
        }

        $this->warnAboutUnlinkedKpiTags();
    }

    /**
     * Bestandsinstallationen: Rolle nachtragen, wenn der Tag noch keine hat und
     * die Rolle noch frei ist (die Migration macht das einmalig, hier als Sicherheitsnetz).
     */
    private function assignRoleIfFree(BiEventTypeTag $tag, ?string $role): void
    {
        if ($role === null || $tag->kpi_role !== null) {
            return;
        }

        if (BiEventTypeTag::query()->where('kpi_role', $role)->exists()) {
            return;
        }

        $tag->update(['kpi_role' => $role]);
        $this->info('  → assigned KPI role "' . $role . '"');
    }

    /**
     * Erstanlage: Terminarten per Namens-Heuristik zuordnen, damit eine frische
     * Installation nicht mit leeren Vorstellungs-Kennzahlen startet.
     */
    private function linkEventTypesByName(BiEventTypeTag $tag): void
    {
        $hints = $tag->kpi_role !== null ? (self::KPI_ROLE_EVENT_TYPE_HINTS[$tag->kpi_role] ?? null) : null;
        if ($hints === null) {
            return;
        }

        $matching = EventType::query()->get(['id', 'name'])->filter(function (EventType $eventType) use ($hints): bool {
            $name = mb_strtolower((string) $eventType->name);
            foreach ($hints as $hint) {
                if (str_contains($name, $hint)) {
                    return true;
                }
            }

            return false;
        });

        if ($matching->isEmpty()) {
            return;
        }

        $tag->eventTypes()->syncWithoutDetaching($matching->pluck('id')->all());
        $this->info(sprintf(
            '  → linked to event types: %s',
            $matching->pluck('name')->implode(', ')
        ));
    }

    private function warnAboutUnlinkedKpiTags(): void
    {
        foreach (BiEventTypeTag::KPI_ROLES as $role) {
            $linked = BiEventTypeTag::query()
                ->where('kpi_role', $role)
                ->has('eventTypes')
                ->exists();

            if (!$linked) {
                $this->warn(sprintf(
                    'No BI tag with KPI role "%s" is linked to an event type — performances, event days and occupancy '
                    . 'stay empty until it is assigned (Settings → Event types → BI tags).',
                    $role
                ));
            }
        }
    }
}

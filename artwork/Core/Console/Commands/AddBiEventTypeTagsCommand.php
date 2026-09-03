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
     * @var array<int, array{name: string, name_de: string, color: string}>
     */
    private const DEFAULT_TAGS = [
        ['name' => 'Event day', 'name_de' => 'Veranstaltungstag', 'color' => '#6366f1'],
        ['name' => 'Performance', 'name_de' => 'Vorstellung', 'color' => '#22c55e'],
        ['name' => 'Rehearsal', 'name_de' => 'Probe', 'color' => '#f59e0b'],
        ['name' => 'Education', 'name_de' => 'Vermittlung', 'color' => '#ec4899'],
        ['name' => 'Special event', 'name_de' => 'Sonderveranstaltung', 'color' => '#06b6d4'],
    ];

    /**
     * Terminarten-Namen (Teilstring, case-insensitive), die bei der ERSTEN Anlage
     * der KPI-Tags automatisch zugeordnet werden. Ohne Zuordnung bleiben
     * Vorstellungen/Veranstaltungstage/Auslastung bewusst leer (kein Fallback).
     *
     * @var array<string, array<int, string>>
     */
    private const KPI_TAG_EVENT_TYPE_HINTS = [
        'Vorstellung' => ['vorstellung', 'premiere', 'derniere', 'dernière', 'aufführung', 'auffuehrung', 'performance', 'konzert', 'show'],
        'Veranstaltungstag' => ['vorstellung', 'premiere', 'derniere', 'dernière', 'aufführung', 'auffuehrung', 'performance', 'konzert', 'show', 'veranstaltung'],
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
                continue;
            }

            $created = BiEventTypeTag::create($tag);
            $this->info('BI event type tag "' . $tag['name_de'] . '" added');
            $this->linkEventTypesByName($created);
        }

        $this->warnAboutUnlinkedKpiTags();
    }

    /**
     * Erstanlage: Terminarten per Namens-Heuristik zuordnen, damit eine frische
     * Installation nicht mit leeren Vorstellungs-Kennzahlen startet.
     */
    private function linkEventTypesByName(BiEventTypeTag $tag): void
    {
        $hints = self::KPI_TAG_EVENT_TYPE_HINTS[$tag->name_de] ?? null;
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
        foreach (array_keys(self::KPI_TAG_EVENT_TYPE_HINTS) as $kpiTag) {
            $linked = BiEventTypeTag::query()
                ->where('name_de', $kpiTag)
                ->has('eventTypes')
                ->exists();

            if (!$linked) {
                $this->warn(sprintf(
                    'BI tag "%s" is not linked to any event type — performances, event days and occupancy '
                    . 'stay empty until it is assigned (Settings → Event types → BI tags).',
                    $kpiTag
                ));
            }
        }
    }
}

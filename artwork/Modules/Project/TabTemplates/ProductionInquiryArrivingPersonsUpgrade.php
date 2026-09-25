<?php

namespace Artwork\Modules\Project\TabTemplates;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Illuminate\Database\DatabaseManager;

/**
 * Stellt bereits aus der Vorlage „Abfrage Produktion“ angelegte Tabs um: Das Textfeld „Anreisende
 * Personen“ wird im Tab durch die CRM-Kontaktliste ersetzt (an derselben Stelle).
 *
 * Erkannt wird das Vorlagen-Textfeld an Typ + Name + Platzhalter (deutsch oder englisch angelegt), damit
 * von Hand gebaute Felder gleichen Namens unberührt bleiben. Hat ein Projekt dort schon Text erfasst,
 * bleibt das alte Textfeld direkt unter der neuen Liste stehen (und wird markiert, damit ein erneuter
 * Lauf es nicht noch einmal ersetzt); sonst wird es entfernt — aber nur, wenn es nirgends mehr steckt.
 *
 * Robustheit: Gibt es den Kontakttyp „Künstler*in“ in der Instanz nicht, bleibt alles unverändert
 * (eine Liste ohne erlaubte Typen wäre leer und gesperrt). Die neue Liste übernimmt die Sichtrechte
 * des Textfelds und wird in der Sprache angelegt, in der das Textfeld angelegt wurde.
 */
class ProductionInquiryArrivingPersonsUpgrade
{
    private const LEGACY_NAMES = ['Names of everyone arriving', 'Anreisende Personen'];

    private const LEGACY_PLACEHOLDERS = [
        'Name, function, email, phone – one person per line',
        'Name, Funktion, E-Mail, Telefon – eine Person pro Zeile',
    ];

    /** Markierung am behaltenen Textfeld (mit Inhalt), damit es nicht erneut ersetzt wird. */
    public const MIGRATED_FLAG = 'arriving_persons_migrated';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ProjectTabTemplateService $templateService,
        private readonly TabTemplateUpgradeSupport $support,
    ) {
    }

    /**
     * @return int Anzahl umgestellter Tab-Einträge
     */
    public function run(): int
    {
        $legacyComponents = Component::query()
            ->where('type', ProjectTabComponentEnum::TEXT_AREA->value)
            ->whereIn('name', self::LEGACY_NAMES)
            ->get()
            ->filter(fn (Component $component) => in_array(
                (string) ($component->data['placeholder'] ?? ''),
                self::LEGACY_PLACEHOLDERS,
                true,
            ) && empty($component->data[self::MIGRATED_FLAG]));

        $replaced = 0;

        foreach ($legacyComponents as $legacy) {
            $replaced += $this->db->transaction(fn (): int => $this->replace($legacy));
        }

        if ($replaced > 0) {
            $this->support->clearComponentCaches();
        }

        return $replaced;
    }

    private function replace(Component $legacy): int
    {
        $placements = ComponentInTab::query()->where('component_id', $legacy->id)->get();
        if ($placements->isEmpty()) {
            return 0;
        }

        $locale = $this->support->localeOf((string) $legacy->name, 'Names of everyone arriving');
        $definition = ProjectTabTemplateCatalog::arrivingPersonsDefinition();
        $data = $this->support->withLocale(
            $locale,
            fn (): array => $this->templateService->buildComponentData($definition)
        );
        if (($data['contact_type_ids'] ?? []) === []) {
            // Kein Kontakttyp „Künstler*in“ in dieser Instanz → Textfeld bleibt, wie es ist.
            return 0;
        }

        $replacement = $this->support->withLocale(
            $locale,
            fn (): Component => $this->templateService->createComponent($definition)
        );
        // Name/Überschrift wie im Bestand (evtl. von Hand angepasst)
        $replacement->update([
            'name' => $legacy->name,
            'data' => array_merge($replacement->data, ['title' => $legacy->data['label'] ?? $legacy->name]),
        ]);
        $this->support->copyPermissions($legacy, $replacement);

        $hasEnteredText = $this->support->hasEnteredText($legacy);

        foreach ($placements as $placement) {
            if ($hasEnteredText) {
                ComponentInTab::query()
                    ->where('project_tab_id', $placement->project_tab_id)
                    ->where('order', '>', $placement->order)
                    ->increment('order');
                ComponentInTab::query()->create([
                    'project_tab_id' => $placement->project_tab_id,
                    'component_id' => $legacy->id,
                    'order' => $placement->order + 1,
                    'scope' => $placement->scope ?? [],
                    'note' => $placement->note,
                ]);
            }

            $placement->update(['component_id' => $replacement->id]);
        }

        if ($hasEnteredText) {
            $legacy->update(['data' => array_merge($legacy->data ?? [], [self::MIGRATED_FLAG => true])]);
        } else {
            $this->support->deleteIfUnused($legacy);
        }

        return $placements->count();
    }
}

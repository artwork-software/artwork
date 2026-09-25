<?php

namespace Artwork\Modules\Project\TabTemplates;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Illuminate\Database\DatabaseManager;

/**
 * Stellt bereits aus der Vorlage „Abfrage Produktion“ angelegte Tabs um: Das Textfeld „Anreisende
 * Personen“ wird im Tab durch die CRM-Kontaktliste ersetzt (an derselben Stelle).
 *
 * Erkannt wird das Vorlagen-Textfeld an Typ + Name + Platzhalter (deutsch oder englisch angelegt), damit
 * von Hand gebaute Felder gleichen Namens unberührt bleiben. Hat ein Projekt dort schon Text erfasst,
 * bleibt das alte Textfeld direkt unter der neuen Liste stehen, damit nichts unsichtbar wird; sonst wird
 * es entfernt.
 */
class ProductionInquiryArrivingPersonsUpgrade
{
    private const LEGACY_NAMES = ['Names of everyone arriving', 'Anreisende Personen'];

    private const LEGACY_PLACEHOLDERS = [
        'Name, function, email, phone – one person per line',
        'Name, Funktion, E-Mail, Telefon – eine Person pro Zeile',
    ];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ProjectTabTemplateService $templateService,
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
            ));

        $replaced = 0;

        foreach ($legacyComponents as $legacy) {
            $replaced += $this->db->transaction(fn (): int => $this->replace($legacy));
        }

        return $replaced;
    }

    private function replace(Component $legacy): int
    {
        $placements = ComponentInTab::query()->where('component_id', $legacy->id)->get();
        if ($placements->isEmpty()) {
            return 0;
        }

        $definition = ProjectTabTemplateCatalog::arrivingPersonsDefinition();
        $replacement = $this->templateService->createComponent($definition);
        // Name/Überschrift wie im Bestand (evtl. von Hand angepasst)
        $replacement->update([
            'name' => $legacy->name,
            'data' => array_merge($replacement->data, ['title' => $legacy->data['label'] ?? $legacy->name]),
        ]);

        $hasEnteredText = ProjectComponentValue::query()
            ->where('component_id', $legacy->id)
            ->get()
            ->contains(fn (ProjectComponentValue $value) => trim((string) ($value->data['text'] ?? '')) !== '');

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

        if (!$hasEnteredText && !ComponentInTab::query()->where('component_id', $legacy->id)->exists()) {
            $legacy->delete();
        }

        return $placements->count();
    }
}

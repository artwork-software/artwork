<?php

namespace Artwork\Modules\Project\TabTemplates;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

/**
 * Stellt Tabs, die aus der ersten Fassung der Vorlage „Abfrage Produktion“ angelegt und danach NICHT
 * umgebaut wurden, auf die aktuelle Fassung um (farbige Abschnittsbalken, Hinweise, CRM-Kontaktliste).
 *
 * „Unverändert“ = dieselben Komponenten (Typ + Name, in der Sprache beim Anlegen) in derselben Reihenfolge
 * wie die Erstfassung. Zugelassen ist, dass „Anreisende Personen“ schon zur Kontaktliste umgestellt wurde
 * (ProductionInquiryArrivingPersonsUpgrade), ggf. mit dem alten Textbereich darunter.
 *
 * Bestehende Komponenten werden an Ort und Stelle aktualisiert (Name, Daten, Hinweis) — erfasste Werte
 * in Projekten bleiben erhalten. Neue Komponenten der aktuellen Fassung werden angelegt, übrig gebliebene
 * alte Einträge bleiben direkt hinter ihrem bisherigen Vorgänger stehen.
 */
class ProductionInquiryTemplateUpgrade
{
    /**
     * Erstfassung der Vorlage: [Typ, Name] in Reihenfolge (Namen als Übersetzungsschlüssel).
     */
    private const FIRST_VERSION = [
        ['Title', 'Contact & company'],
        ['TextArea', 'Name and contractual address of the company'],
        ['TextArea', 'Contact person for the production'],
        ['TextField', 'Email address for all queries'],
        ['Title', 'Artists'],
        ['TextField', 'Number of arriving artists'],
        ['TextArea', 'Names of everyone arriving'],
        ['TextArea', 'Social media handles and further links'],
        ['Title', 'General information'],
        ['TextField', 'Title of the production'],
        ['TextField', 'Duration of the production (minutes, without break)'],
        ['TextField', 'Age recommendation'],
        ['TextField', 'Language of the performance'],
        ['Title', 'Documents'],
        ['ProjectDocumentsComponent', null],
        ['Checkbox', 'Additional educational offer available'],
        ['Title', 'Music & rights'],
        ['TextArea', 'List of music used in the performance'],
        ['Title', 'Anything else'],
        ['DropDown', 'Wardrobe service required?'],
        ['TextArea', 'Other remarks'],
        ['Title', 'Texts & description'],
        ['TextArea', 'Short description / teaser'],
        ['TextField', 'Subtitle and credits'],
        ['TextArea', 'Abstract for the production'],
        ['Title', 'Images & video'],
        ['Link', 'Link to download the images'],
        ['TextArea', 'Photo credits'],
        ['Link', 'Trailer / teaser link'],
        ['TextArea', 'Video credits'],
        ['Title', 'Sponsors & partners'],
        ['TextArea', 'Sponsors, supporters, partners'],
    ];

    private const ARRIVING_PERSONS = 'Names of everyone arriving';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ProjectTabTemplateService $templateService,
    ) {
    }

    /**
     * @return int Anzahl umgestellter Tabs
     */
    public function run(): int
    {
        $upgraded = 0;

        foreach (ProjectTab::query()->orderBy('id')->get() as $tab) {
            $rows = ComponentInTab::query()
                ->where('project_tab_id', $tab->id)
                ->with('component')
                ->orderBy('order')
                ->orderBy('id')
                ->get();

            $matched = $this->matchFirstVersion($rows);
            if ($matched === null) {
                continue;
            }

            $this->db->transaction(fn () => $this->upgradeTab($tab, $rows, $matched));
            $upgraded++;
        }

        return $upgraded;
    }

    /**
     * Ordnet die Tab-Zeilen der Erstfassung zu. Ergebnis: Erstfassungs-Name (bzw. Typ bei System-
     * Komponenten) → Zeile; null, wenn der Tab nicht der unveränderten Erstfassung entspricht.
     *
     * @param Collection<int, ComponentInTab> $rows
     * @return array<string, ComponentInTab>|null
     */
    private function matchFirstVersion(Collection $rows): ?array
    {
        $matched = [];
        $index = 0;

        foreach (self::FIRST_VERSION as [$type, $name]) {
            $row = $rows->get($index);
            if ($row === null || $row->component === null) {
                return null;
            }

            $isArrivingPersons = $name === self::ARRIVING_PERSONS;
            $typeMatches = $row->component->type === $type
                || ($isArrivingPersons && $row->component->type === ProjectTabComponentEnum::CRM_CONTACT_LIST->value);

            if (!$typeMatches || ($name !== null && !$this->nameMatches($row->component, $name))) {
                return null;
            }

            $matched[$name ?? $type] = $row;
            $index++;

            // Nach der Teil-Umstellung kann der alte Textbereich (mit Inhalt) direkt folgen
            if ($isArrivingPersons && $row->component->type === ProjectTabComponentEnum::CRM_CONTACT_LIST->value) {
                $next = $rows->get($index);
                if (
                    $next?->component?->type === ProjectTabComponentEnum::TEXT_AREA->value
                    && $this->nameMatches($next->component, self::ARRIVING_PERSONS)
                ) {
                    $index++;
                }
            }
        }

        return $index === $rows->count() ? $matched : null;
    }

    private function nameMatches(Component $component, string $key): bool
    {
        $candidates = array_unique([$key, __($key, [], 'de'), __($key, [], 'en'), __($key)]);

        return in_array(trim((string) $component->name), $candidates, true);
    }

    /**
     * @param Collection<int, ComponentInTab> $rows
     * @param array<string, ComponentInTab> $matched
     */
    private function upgradeTab(ProjectTab $tab, Collection $rows, array $matched): void
    {
        $template = ProjectTabTemplateCatalog::find(ProjectTabTemplateCatalog::PRODUCTION_INQUIRY);

        /** @var array<int, ComponentInTab> $finalRows */
        $finalRows = [];
        $consumedIds = [];

        foreach ($template['components'] as $definition) {
            $legacy = $definition['legacy'] ?? null;
            $existing = $legacy !== null ? ($matched[$legacy] ?? null) : null;
            $note = isset($definition['note']) && $definition['note'] !== '' ? __($definition['note']) : null;

            if ($existing !== null && $this->canBeUpdatedInPlace($existing, $definition)) {
                if (!isset($definition['special'])) {
                    $existing->component->update([
                        'name' => __($definition['name']),
                        'data' => $this->templateService->buildComponentData($definition),
                    ]);
                }
                $existing->note = $note;
                $finalRows[] = $existing;
                $consumedIds[] = $existing->id;
                continue;
            }

            $component = isset($definition['special'])
                ? $this->templateService->resolveSpecialComponent($definition['special'])
                : $this->templateService->createComponent($definition);
            if ($component === null) {
                continue;
            }

            $finalRows[] = new ComponentInTab([
                'project_tab_id' => $tab->id,
                'component_id' => $component->id,
                'scope' => isset($definition['special']) ? [$tab->id] : [],
                'note' => $note,
            ]);

            // Ersetzter Eintrag der Erstfassung (z. B. Textbereich → Kontaktliste) bleibt nur, wenn er
            // Inhalte hat; sonst verschwindet er aus dem Tab.
            if ($existing !== null) {
                $consumedIds[] = $existing->id;
                if ($this->hasEnteredValues($existing->component)) {
                    $finalRows[] = $existing;
                } else {
                    $replacedComponent = $existing->component;
                    $existing->delete();
                    if (!ComponentInTab::query()->where('component_id', $replacedComponent->id)->exists()) {
                        $replacedComponent->delete();
                    }
                }
            }
        }

        // Übrige Zeilen (z. B. alter Textbereich unter der Kontaktliste) hinter ihrem bisherigen Vorgänger
        $previous = null;
        foreach ($rows as $row) {
            if (in_array($row->id, $consumedIds, true)) {
                $previous = $row;
                continue;
            }
            $position = $previous !== null ? $this->positionOf($finalRows, $previous) : -1;
            array_splice($finalRows, $position + 1, 0, [$row]);
            $previous = $row;
        }

        foreach (array_values($finalRows) as $order => $row) {
            $row->order = $order;
            $row->save();
        }
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function canBeUpdatedInPlace(ComponentInTab $existing, array $definition): bool
    {
        $type = $definition['special'] ?? $definition['type'];

        return $existing->component?->type === $type;
    }

    private function hasEnteredValues(?Component $component): bool
    {
        if ($component === null) {
            return false;
        }

        return ProjectComponentValue::query()
            ->where('component_id', $component->id)
            ->get()
            ->contains(fn (ProjectComponentValue $value) => trim((string) ($value->data['text'] ?? '')) !== '');
    }

    /**
     * @param array<int, ComponentInTab> $rows
     */
    private function positionOf(array $rows, ComponentInTab $needle): int
    {
        foreach (array_values($rows) as $position => $row) {
            if ($row->id !== null && $row->id === $needle->id) {
                return $position;
            }
        }

        return count($rows) - 1;
    }
}

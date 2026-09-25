<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\TabTemplates\ProductionInquiryTemplateUpgrade;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateCatalog;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClassConstant;
use Tests\TestCase;

final class ProductionInquiryTemplateUpgradeTest extends TestCase
{
    /**
     * Tab wie aus der Erstfassung der Vorlage angelegt (deutsche Namen).
     *
     * @return array{tab: ProjectTab, rows: array<string, ComponentInTab>}
     */
    private function firstVersionTab(): array
    {
        $firstVersion = (new ReflectionClassConstant(ProductionInquiryTemplateUpgrade::class, 'FIRST_VERSION'))->getValue();
        $tab = ProjectTab::factory()->create();
        $rows = [];

        foreach ($firstVersion as $order => [$type, $name]) {
            $component = $name === null
                ? Component::query()->firstOrCreate(
                    ['type' => ProjectTabComponentEnum::PROJECT_DOCUMENTS->value, 'special' => true],
                    ['name' => 'Dokumente', 'data' => []],
                )
                : Component::create([
                    'name' => __($name, [], 'de'),
                    'type' => $type,
                    'data' => $type === 'Title'
                        ? ['title' => __($name, [], 'de'), 'title_size' => 16]
                        : ['label' => __($name, [], 'de'), 'text' => '', 'placeholder' => 'alt'],
                ]);
            $rows[$name ?? $type] = ComponentInTab::create([
                'project_tab_id' => $tab->id,
                'component_id' => $component->id,
                'order' => $order,
                'scope' => $name === null ? [$tab->id] : [],
            ]);
        }

        return ['tab' => $tab, 'rows' => $rows];
    }

    /**
     * @return array<int, string>
     */
    private function typesInTab(ProjectTab $tab): array
    {
        return ComponentInTab::query()->where('project_tab_id', $tab->id)->orderBy('order')->with('component')->get()
            ->pluck('component.type')->all();
    }

    #[Test]
    public function unchanged_first_version_tab_is_upgraded_and_keeps_entered_values(): void
    {
        ['tab' => $tab, 'rows' => $rows] = $this->firstVersionTab();
        $addressComponentId = $rows['Name and contractual address of the company']->component_id;
        ProjectComponentValue::create([
            'project_id' => Project::factory()->create()->id,
            'component_id' => $addressComponentId,
            'data' => ['text' => 'Compagnie Luna, Basel'],
        ]);

        $this->assertSame(1, app(ProductionInquiryTemplateUpgrade::class)->run());

        $expectedTypes = array_map(
            static fn (array $definition): string => $definition['type'] ?? $definition['special'],
            ProjectTabTemplateCatalog::find(ProjectTabTemplateCatalog::PRODUCTION_INQUIRY)['components'],
        );
        $this->assertSame($expectedTypes, $this->typesInTab($tab));

        // bestehende Komponente an Ort und Stelle aktualisiert → Projektwert bleibt sichtbar
        $addressRow = ComponentInTab::query()->where('project_tab_id', $tab->id)->where('component_id', $addressComponentId)->first();
        $this->assertNotNull($addressRow);
        $this->assertSame('', $addressRow->component->fresh()->data['placeholder']);
        $this->assertNotEmpty($addressRow->note);

        // Abschnittsüberschriften bekommen den Balken
        $sectionTitle = $rows['Contact & company']->component->fresh();
        $this->assertSame(ProjectTabTemplateCatalog::SECTION_BAR_COLOR, $sectionTitle->data['bar_color']);

        // Textbereich „Anreisende Personen“ ohne Inhalt ist durch die Kontaktliste ersetzt
        $this->assertNull(Component::query()->find($rows['Names of everyone arriving']->component_id));
    }

    #[Test]
    public function arriving_persons_text_with_content_stays_below_the_contact_list(): void
    {
        ['tab' => $tab, 'rows' => $rows] = $this->firstVersionTab();
        ProjectComponentValue::create([
            'project_id' => Project::factory()->create()->id,
            'component_id' => $rows['Names of everyone arriving']->component_id,
            'data' => ['text' => 'Anna, Tanz'],
        ]);

        app(ProductionInquiryTemplateUpgrade::class)->run();

        $types = $this->typesInTab($tab);
        $listPosition = array_search(ProjectTabComponentEnum::CRM_CONTACT_LIST->value, $types, true);
        $this->assertNotFalse($listPosition);
        $this->assertSame(ProjectTabComponentEnum::TEXT_AREA->value, $types[$listPosition + 1]);
        $this->assertTrue(ComponentInTab::query()->where('component_id', $rows['Names of everyone arriving']->component_id)->exists());
    }

    #[Test]
    public function modified_tabs_are_left_alone(): void
    {
        ['tab' => $tab] = $this->firstVersionTab();
        $extra = Component::create(['name' => 'Eigenes Feld', 'type' => 'TextField', 'data' => ['label' => 'Eigenes Feld']]);
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $extra->id, 'order' => 99]);
        $before = $this->typesInTab($tab);

        $this->assertSame(0, app(ProductionInquiryTemplateUpgrade::class)->run());
        $this->assertSame($before, $this->typesInTab($tab));
    }

    #[Test]
    public function upgrade_is_idempotent(): void
    {
        $this->firstVersionTab();

        $this->assertSame(1, app(ProductionInquiryTemplateUpgrade::class)->run());
        $this->assertSame(0, app(ProductionInquiryTemplateUpgrade::class)->run());
    }
}

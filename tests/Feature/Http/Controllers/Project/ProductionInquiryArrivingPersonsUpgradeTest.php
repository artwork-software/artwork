<?php

namespace Tests\Feature\Http\Controllers\Project;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\TabTemplates\ProductionInquiryArrivingPersonsUpgrade;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateCatalog;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProductionInquiryArrivingPersonsUpgradeTest extends TestCase
{
    private function legacyTextArea(string $name = 'Anreisende Personen'): Component
    {
        return Component::create([
            'name' => $name,
            'type' => ProjectTabComponentEnum::TEXT_AREA->value,
            'data' => ['label' => $name, 'text' => '', 'placeholder' => 'Name, Funktion, E-Mail, Telefon – eine Person pro Zeile'],
        ]);
    }

    #[Test]
    public function template_creates_a_crm_contact_list_instead_of_a_text_area(): void
    {
        $tab = app(ProjectTabTemplateService::class)->apply(ProjectTabTemplateCatalog::PRODUCTION_INQUIRY);

        $types = ComponentInTab::query()->where('project_tab_id', $tab->id)->with('component')->get()
            ->pluck('component.type')->all();

        $this->assertContains(ProjectTabComponentEnum::CRM_CONTACT_LIST->value, $types);
    }

    #[Test]
    public function legacy_text_area_is_replaced_in_place(): void
    {
        $tab = ProjectTab::factory()->create();
        $before = Component::create(['name' => 'Vorher', 'type' => 'TextField', 'data' => []]);
        $legacy = $this->legacyTextArea();
        $after = Component::create(['name' => 'Nachher', 'type' => 'TextField', 'data' => []]);
        foreach ([$before, $legacy, $after] as $order => $component) {
            ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => $order]);
        }

        $this->assertSame(1, app(ProductionInquiryArrivingPersonsUpgrade::class)->run());

        $rows = ComponentInTab::query()->where('project_tab_id', $tab->id)->orderBy('order')->with('component')->get();
        $this->assertSame(['TextField', ProjectTabComponentEnum::CRM_CONTACT_LIST->value, 'TextField'], $rows->pluck('component.type')->all());
        $this->assertSame('Anreisende Personen', $rows[1]->component->data['title']);
        $this->assertNull(Component::query()->find($legacy->id));
    }

    #[Test]
    public function legacy_text_area_with_entered_text_stays_below_the_new_list(): void
    {
        $tab = ProjectTab::factory()->create();
        $legacy = $this->legacyTextArea();
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $legacy->id, 'order' => 0]);
        ProjectComponentValue::create([
            'project_id' => Project::factory()->create()->id,
            'component_id' => $legacy->id,
            'data' => ['text' => 'Anna, Tanz'],
        ]);

        app(ProductionInquiryArrivingPersonsUpgrade::class)->run();

        $types = ComponentInTab::query()->where('project_tab_id', $tab->id)->orderBy('order')->with('component')->get()
            ->pluck('component.type')->all();
        $this->assertSame([ProjectTabComponentEnum::CRM_CONTACT_LIST->value, ProjectTabComponentEnum::TEXT_AREA->value], $types);
    }

    #[Test]
    public function hand_made_text_areas_with_the_same_name_are_left_alone(): void
    {
        $tab = ProjectTab::factory()->create();
        $custom = Component::create([
            'name' => 'Anreisende Personen',
            'type' => ProjectTabComponentEnum::TEXT_AREA->value,
            'data' => ['label' => 'Anreisende Personen', 'text' => '', 'placeholder' => 'eigener Platzhalter'],
        ]);
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $custom->id, 'order' => 0]);

        app(ProductionInquiryArrivingPersonsUpgrade::class)->run();

        $this->assertTrue(ComponentInTab::query()->where('component_id', $custom->id)->exists());
    }
}

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
    protected function setUp(): void
    {
        parent::setUp();

        // Die Kontaktliste der Vorlage erlaubt den Typ „Künstler*in“ — ohne ihn wird nichts umgestellt.
        \Artwork\Modules\Crm\Models\CrmContactType::withTrashed()->firstOrCreate(
            ['slug' => 'artist'],
            ['name' => 'Künstler*in', 'is_system' => true, 'is_active' => true],
        )->restore();
    }

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

    #[Test]
    public function legacy_text_area_used_in_a_print_layout_does_not_break_the_migration(): void
    {
        $tab = ProjectTab::factory()->create();
        $legacy = $this->legacyTextArea();
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $legacy->id, 'order' => 0]);
        $layout = \Artwork\Modules\Project\Models\ProjectPrintLayout::create([
            'name' => 'Abfrage', 'description' => '', 'columns_header' => 1, 'columns_body' => 1,
            'columns_footer' => 1, 'order' => 1, 'user_id' => $this->adminUser()->id,
            'notes' => ['header' => [], 'footer' => []],
        ]);
        \Artwork\Modules\Project\Models\PrintLayoutComponents::create([
            'project_print_layout_id' => $layout->id, 'component_id' => $legacy->id,
            'type' => 'body', 'row' => 1, 'position' => 1,
        ]);

        $this->assertSame(1, app(ProductionInquiryArrivingPersonsUpgrade::class)->run());

        // Komponente bleibt (steckt noch im Drucklayout), der Tab zeigt die Kontaktliste
        $this->assertNotNull(Component::query()->find($legacy->id));
        $this->assertSame(
            [ProjectTabComponentEnum::CRM_CONTACT_LIST->value],
            ComponentInTab::query()->where('project_tab_id', $tab->id)->with('component')->get()->pluck('component.type')->all(),
        );
    }

    #[Test]
    public function running_twice_does_not_add_a_second_contact_list(): void
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
        $this->assertSame(0, app(ProductionInquiryArrivingPersonsUpgrade::class)->run());

        $this->assertSame(2, ComponentInTab::query()->where('project_tab_id', $tab->id)->count());
    }

    #[Test]
    public function contact_list_keeps_the_visibility_restrictions_of_the_text_area(): void
    {
        $tab = ProjectTab::factory()->create();
        $legacy = $this->legacyTextArea();
        $legacy->update(['permission_type' => 'someSeeSomeEdit']);
        $user = \Artwork\Modules\User\Models\User::factory()->create();
        $legacy->users()->attach($user->id, ['can_write' => true]);
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $legacy->id, 'order' => 0]);

        app(ProductionInquiryArrivingPersonsUpgrade::class)->run();

        $list = ComponentInTab::query()->where('project_tab_id', $tab->id)->first()->component;
        $this->assertSame('someSeeSomeEdit', $list->permission_type);
        $this->assertSame([$user->id], $list->users()->pluck('users.id')->all());
    }

    #[Test]
    public function nothing_changes_without_an_artist_contact_type(): void
    {
        \Artwork\Modules\Crm\Models\CrmContactType::query()->where('slug', 'artist')->forceDelete();
        $tab = ProjectTab::factory()->create();
        $legacy = $this->legacyTextArea();
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $legacy->id, 'order' => 0]);

        $this->assertSame(0, app(ProductionInquiryArrivingPersonsUpgrade::class)->run());
        $this->assertSame($legacy->id, ComponentInTab::query()->where('project_tab_id', $tab->id)->value('component_id'));
    }
}

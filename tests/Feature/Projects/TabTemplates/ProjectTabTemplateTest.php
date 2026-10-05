<?php

namespace Tests\Feature\Projects\TabTemplates;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateCatalog;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProjectTabTemplateTest extends TestCase
{
    #[Test]
    public function template_creates_a_normal_tab_with_normal_components(): void
    {
        Component::query()->firstOrCreate(
            ['type' => ProjectTabComponentEnum::PROJECT_DOCUMENTS->value, 'special' => true],
            ['name' => 'Project Documents', 'data' => []],
        );
        $componentsBefore = Component::query()->count();

        $tab = app(ProjectTabTemplateService::class)->apply(ProjectTabTemplateCatalog::PRODUCTION_INQUIRY);

        $definition = ProjectTabTemplateCatalog::find(ProjectTabTemplateCatalog::PRODUCTION_INQUIRY);
        $expectedNew = count(array_filter($definition['components'], fn ($c) => !isset($c['special'])));

        $this->assertTrue($tab->visible_for_all);
        $this->assertSame(count($definition['components']), $tab->components()->count());
        // Es entstehen genau die neuen (nicht speziellen) Komponenten – kein Vorlagen-Zustand
        $this->assertSame($componentsBefore + $expectedNew, Component::query()->count());
        $this->assertSame(0, Component::query()->where('special', false)->whereNull('permission_type')->count());

        $documentsInTab = $tab->components()
            ->whereHas('component', fn ($q) => $q->where('type', ProjectTabComponentEnum::PROJECT_DOCUMENTS->value))
            ->first();
        $this->assertNotNull($documentsInTab);
        $this->assertSame([$tab->id], $documentsInTab->scope);
        $this->assertNotEmpty($documentsInTab->note);

        $dropdown = Component::query()->where('type', ProjectTabComponentEnum::DROPDOWN->value)->latest('id')->first();
        $this->assertCount(3, $dropdown->data['options']);
        $this->assertSame(['value'], array_keys($dropdown->data['options'][0]));
    }

    #[Test]
    public function every_production_inquiry_component_type_is_externally_readable(): void
    {
        // Die Abfrage-Vorlage ist für Externe gedacht; die Standard-Tabs (Werkzeuge) sind es nicht.
        foreach ([ProjectTabTemplateCatalog::find(ProjectTabTemplateCatalog::PRODUCTION_INQUIRY)] as $template) {
            foreach ($template['components'] as $component) {
                $type = ProjectTabComponentEnum::from($component['type'] ?? $component['special']);
                $this->assertTrue($type->isExternallyReadable(), "{$type->value} is not externally readable");
            }
        }
    }

    #[Test]
    public function apply_route_requires_project_settings_permission_and_creates_tab(): void
    {
        $this->actingAsAdmin();
        $before = ProjectTab::query()->count();

        $this->post(route('tab.templates.apply', ProjectTabTemplateCatalog::PRODUCTION_INQUIRY))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($before + 1, ProjectTab::query()->count());
        $this->post(route('tab.templates.apply', 'does-not-exist'))->assertNotFound();
    }

    #[Test]
    public function tab_settings_page_lists_templates(): void
    {
        $this->actingAsAdmin();

        $this->get(route('tab.index'))->assertInertia(fn ($page) => $page
            ->has('tabTemplates', count(ProjectTabTemplateCatalog::all()))
            ->where('tabTemplates.0.key', ProjectTabTemplateCatalog::PROJECT_INFORMATION)
            ->where(
                'tabTemplates.' . (count(ProjectTabTemplateCatalog::all()) - 1) . '.key',
                ProjectTabTemplateCatalog::PRODUCTION_INQUIRY,
            ));
    }
}

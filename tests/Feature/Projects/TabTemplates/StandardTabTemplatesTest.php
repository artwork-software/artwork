<?php

namespace Tests\Feature\Projects\TabTemplates;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateCatalog;
use Artwork\Modules\Project\TabTemplates\ProjectTabTemplateService;
use Database\Seeders\DefaultComponentSeeder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class StandardTabTemplatesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function standardTabs(): array
    {
        return array_combine(
            ProjectTabTemplateCatalog::STANDARD_TABS,
            array_map(static fn (string $key): array => [$key], ProjectTabTemplateCatalog::STANDARD_TABS),
        );
    }

    #[Test]
    #[DataProvider('standardTabs')]
    public function every_standard_tab_is_available_as_template_with_its_sidebar(string $key): void
    {
        $template = ProjectTabTemplateCatalog::find($key);
        $tab = app(ProjectTabTemplateService::class)->apply($key);

        $this->assertSame(count($template['sidebar']), $tab->sidebarTabs()->count());
        $this->assertGreaterThan(0, $tab->sidebarTabs()->first()->componentsInSidebar()->count());
        $this->assertContains($key, array_column(app(ProjectTabTemplateService::class)->listForUi(), 'key'));
    }

    #[Test]
    public function project_information_is_structured_with_section_bars_and_hints(): void
    {
        $tab = app(ProjectTabTemplateService::class)->apply(ProjectTabTemplateCatalog::PROJECT_INFORMATION);
        $rows = ComponentInTab::query()->where('project_tab_id', $tab->id)->orderBy('order')->with('component')->get();

        $bars = $rows->filter(fn (ComponentInTab $row) => $row->component->type === ProjectTabComponentEnum::TITLE->value);
        $this->assertCount(2, $bars);
        foreach ($bars as $bar) {
            $this->assertSame(ProjectTabTemplateCatalog::SECTION_BAR_COLOR, $bar->component->data['bar_color']);
        }

        $textAreas = $rows->filter(fn (ComponentInTab $row) => $row->component->type === ProjectTabComponentEnum::TEXT_AREA->value);
        $this->assertCount(3, $textAreas);
        $textAreas->each(fn (ComponentInTab $row) => $this->assertNotEmpty($row->note));
    }

    #[Test]
    public function tool_tabs_have_no_tab_scope_but_collecting_components_do(): void
    {
        $service = app(ProjectTabTemplateService::class);
        $shifts = $service->apply(ProjectTabTemplateCatalog::SHIFTS);
        $comments = $service->apply(ProjectTabTemplateCatalog::COMMENTS);

        $this->assertSame([], ComponentInTab::query()->where('project_tab_id', $shifts->id)->first()->scope);
        $this->assertSame([$comments->id], ComponentInTab::query()->where('project_tab_id', $comments->id)->first()->scope);
    }

    #[Test]
    public function default_seeder_creates_the_standard_tabs_from_the_templates(): void
    {
        $before = ProjectTab::query()->count();

        $this->seed(DefaultComponentSeeder::class);

        $this->assertSame($before + count(ProjectTabTemplateCatalog::STANDARD_TABS), ProjectTab::query()->count());
        $projectInformation = ProjectTab::query()->orderByDesc('id')->skip(count(ProjectTabTemplateCatalog::STANDARD_TABS) - 1)->first();
        $this->assertTrue(
            ComponentInTab::query()->where('project_tab_id', $projectInformation->id)
                ->whereHas('component', fn ($q) => $q->where('type', ProjectTabComponentEnum::TITLE->value))
                ->exists()
        );
    }
}

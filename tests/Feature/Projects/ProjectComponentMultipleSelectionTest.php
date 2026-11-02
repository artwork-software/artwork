<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\BusinessIntelligence\Services\BiExportService;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Support\ComponentSelection;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Feature\FeatureTestCase;

/**
 * Mehrfachauswahl: neue Komponente CheckboxList und DropDown mit "multiple". Werte sind Listen; das Abschalten
 * der Mehrfachauswahl ist nur erlaubt, solange kein Projekt mehrere Werte gewählt hat.
 */
final class ProjectComponentMultipleSelectionTest extends FeatureTestCase
{
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        $this->project = Project::factory()->create();
    }

    #[Test]
    public function checkbox_list_stores_a_list_in_option_order(): void
    {
        $list = $this->makeComponent('CheckboxList', ['options' => [['value' => 'A'], ['value' => 'B'], ['value' => 'C']]]);

        $this->patchValue($list, ['selected' => ['C', 'A', 'A']])->assertOk();
        $this->assertSame(['selected' => ['A', 'C']], $this->storedData($list));

        $this->patchValue($list, ['selected' => ['X']])->assertUnprocessable();
        $this->assertSame(['selected' => ['A', 'C']], $this->storedData($list));

        $this->patchValue($list, ['selected' => []])->assertOk();
        $this->assertSame(['selected' => []], $this->storedData($list));
    }

    #[Test]
    public function dropdown_with_multiple_selection_stores_lists_and_accepts_single_values(): void
    {
        $dropdown = $this->makeComponent('DropDown', [
            'options' => [['value' => 'Tanz'], ['value' => 'Theater']],
            'multiple' => true,
        ]);

        $this->patchValue($dropdown, ['selected' => ['Theater', 'Tanz']])->assertOk();
        $this->assertSame(['selected' => ['Tanz', 'Theater']], $this->storedData($dropdown));

        // älterer Client schickt einen Einzelwert
        $this->patchValue($dropdown, ['selected' => 'Theater'])->assertOk();
        $this->assertSame(['selected' => ['Theater']], $this->storedData($dropdown));
    }

    #[Test]
    public function reordering_options_keeps_the_project_selection(): void
    {
        $list = $this->makeComponent('CheckboxList', ['options' => [['value' => 'A'], ['value' => 'B'], ['value' => 'C']]]);
        $this->patchValue($list, ['selected' => ['A', 'C']])->assertOk();

        $this->updateComponentData($list, ['options' => [['value' => 'C'], ['value' => 'A'], ['value' => 'B']]])
            ->assertSessionHasNoErrors();

        $this->assertSame([['value' => 'C'], ['value' => 'A'], ['value' => 'B']], $list->fresh()->data['options']);
        $this->assertSame(['selected' => ['A', 'C']], $this->storedData($list));

        // nächstes Speichern folgt der neuen Reihenfolge
        $this->patchValue($list, ['selected' => ['A', 'C']])->assertOk();
        $this->assertSame(['selected' => ['C', 'A']], $this->storedData($list));
    }

    #[Test]
    public function stale_entries_of_a_renamed_option_do_not_block_saving(): void
    {
        $dropdown = $this->makeComponent('DropDown', [
            'options' => [['value' => 'A'], ['value' => 'B']],
            'multiple' => true,
        ]);
        $this->patchValue($dropdown, ['selected' => ['A', 'B']])->assertOk();

        // Option "B" wird in "C" umbenannt; die Auswahl schickt den Altwert beim nächsten Klick mit
        $dropdown->update(['data' => [...$dropdown->data, 'options' => [['value' => 'A'], ['value' => 'C']]]]);

        $this->patchValue($dropdown, ['selected' => ['A', 'B', 'C']])->assertOk();
        $this->assertSame(['selected' => ['A', 'C']], $this->storedData($dropdown));

        // erfundene Werte bleiben ungültig
        $this->patchValue($dropdown, ['selected' => ['A', 'X']])->assertUnprocessable();
        $this->assertSame(['selected' => ['A', 'C']], $this->storedData($dropdown));
    }

    #[Test]
    public function turning_multiple_selection_on_converts_existing_single_values(): void
    {
        $dropdown = $this->makeComponent('DropDown', ['options' => [['value' => 'Tanz'], ['value' => 'Theater']]]);
        $this->patchValue($dropdown, ['selected' => 'Tanz'])->assertOk();

        $this->updateComponentData($dropdown, ['multiple' => true])->assertSessionHasNoErrors();

        $this->assertSame(['selected' => ['Tanz']], $this->storedData($dropdown));
    }

    #[Test]
    public function multiple_selection_can_only_be_turned_off_without_several_values(): void
    {
        $dropdown = $this->makeComponent('DropDown', [
            'options' => [['value' => 'Tanz'], ['value' => 'Theater']],
            'multiple' => true,
        ]);
        $this->patchValue($dropdown, ['selected' => ['Tanz', 'Theater']])->assertOk();

        $this->updateComponentData($dropdown, ['multiple' => false])->assertSessionHasErrors('data.multiple');
        $this->assertTrue($dropdown->fresh()->data['multiple']);

        $this->patchValue($dropdown, ['selected' => ['Theater']])->assertOk();
        $this->updateComponentData($dropdown, ['multiple' => false])->assertSessionHasNoErrors();

        $this->assertFalse($dropdown->fresh()->data['multiple']);
        $this->assertSame(['selected' => 'Theater'], $this->storedData($dropdown));
    }

    #[Test]
    public function bi_export_lists_several_values_comma_separated(): void
    {
        $dropdown = $this->makeComponent('DropDown', [
            'options' => [['value' => 'Tanz'], ['value' => 'Theater']],
            'multiple' => true,
        ]);
        ProjectComponentValue::create([
            'project_id' => $this->project->id,
            'component_id' => $dropdown->id,
            'data' => ['selected' => ['Tanz', 'Theater']],
        ]);

        $method = new ReflectionMethod(BiExportService::class, 'getCustomFieldValue');

        $this->assertSame('Tanz, Theater', $method->invoke(app(BiExportService::class), $this->project, $dropdown->id));
    }

    #[Test]
    public function selection_helper_reads_all_stored_forms(): void
    {
        $this->assertSame(['A'], ComponentSelection::values('A'));
        $this->assertSame(['A'], ComponentSelection::values(['value' => 'A']));
        $this->assertSame(['A', 'B'], ComponentSelection::values(['A', ['value' => 'B'], '', 'A']));
        $this->assertSame([], ComponentSelection::values(null));
        $this->assertSame('A, B', ComponentSelection::displayText(['A', 'B']));
        $this->assertTrue(ComponentSelection::allowsMultiple('CheckboxList', []));
        $this->assertFalse(ComponentSelection::allowsMultiple('DropDown', ['multiple' => false]));
    }

    /**
     * @param array<string, mixed> $componentData
     */
    private function makeComponent(string $type, array $componentData = []): Component
    {
        return Component::create([
            'name' => $type,
            'type' => $type,
            'data' => ['label' => $type, ...$componentData],
            'permission_type' => 'allSeeAndEdit',
        ]);
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function updateComponentData(Component $component, array $changes): TestResponse
    {
        return $this->from(route('component.index'))->patch(route('component.update', $component), [
            'name' => $component->name,
            'data' => [...$component->fresh()->data, ...$changes],
            'permission_type' => 'allSeeAndEdit',
        ]);
    }

    /**
     * @param array<string, mixed>|null $data
     */
    private function patchValue(Component $component, ?array $data): TestResponse
    {
        return $this->patchJson(route('project.tab.component.update', [
            'project' => $this->project->id,
            'component' => $component->id,
        ]), ['data' => $data]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function storedData(Component $component): ?array
    {
        return ProjectComponentValue::query()
            ->where('project_id', $this->project->id)
            ->where('component_id', $component->id)
            ->first()?->data;
    }
}

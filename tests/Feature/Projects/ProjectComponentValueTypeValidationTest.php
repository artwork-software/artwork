<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Interner Speicherweg der Tab-Komponenten prüft den Wert je Typ — dieselbe Prüfung wie extern
 * (ProjectComponentValueNormalizer), damit beide Wege dieselbe Wertform speichern.
 */
final class ProjectComponentValueTypeValidationTest extends FeatureTestCase
{
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        $this->project = Project::factory()->create();
    }

    /**
     * @param array<string, mixed> $componentData
     */
    private function makeComponent(string $type, array $componentData = []): Component
    {
        return Component::create([
            'name' => $type,
            'type' => $type,
            'data' => $componentData,
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

    #[Test]
    public function dropdown_accepts_only_configured_options(): void
    {
        $dropdown = $this->makeComponent('DropDown', ['options' => [['value' => 'Tanz'], ['value' => 'Konzert']]]);

        $this->patchValue($dropdown, ['selected' => 'Freitext'])->assertUnprocessable();
        $this->assertNull($this->storedData($dropdown));

        $this->patchValue($dropdown, ['selected' => 'Konzert'])->assertOk();
        $this->assertSame(['selected' => 'Konzert'], $this->storedData($dropdown));

        // Auswahl aufheben (intern null, extern "")
        $this->patchValue($dropdown, ['selected' => ''])->assertOk();
        $this->assertSame(['selected' => null], $this->storedData($dropdown));
    }

    #[Test]
    public function checkbox_is_stored_as_boolean(): void
    {
        $checkbox = $this->makeComponent('Checkbox');

        $this->patchValue($checkbox, ['checked' => 'kein-bool'])->assertUnprocessable();

        $this->patchValue($checkbox, ['checked' => true, 'text' => 'fremd'])->assertOk();
        $this->assertSame(['checked' => true], $this->storedData($checkbox));
    }

    #[Test]
    public function text_field_keeps_only_text_and_rejects_lists(): void
    {
        $textField = $this->makeComponent('TextField');

        $this->patchValue($textField, ['text' => ['a', 'b']])->assertUnprocessable();

        $this->patchValue($textField, ['text' => 42, 'checked' => true])->assertOk();
        $this->assertSame(['text' => '42'], $this->storedData($textField));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function dangerousLinkTargets(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'mixed case with whitespace' => [" JaVa\tScRiPt:alert(1)"],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'vbscript' => ['vbscript:msgbox(1)'],
        ];
    }

    #[Test]
    #[DataProvider('dangerousLinkTargets')]
    public function link_rejects_executable_targets(string $target): void
    {
        $link = $this->makeComponent('Link');

        $this->patchValue($link, ['text' => $target])->assertUnprocessable();
        $this->assertNull($this->storedData($link));
    }

    #[Test]
    public function link_accepts_addresses_without_scheme(): void
    {
        $link = $this->makeComponent('Link');

        $this->patchValue($link, ['text' => 'www.beispiel.de'])->assertOk();
        $this->assertSame(['text' => 'www.beispiel.de'], $this->storedData($link));
    }

    #[Test]
    public function links_accept_relative_paths_and_keep_other_targets_as_text(): void
    {
        // LNK-1: relative Pfade öffnet die Anzeige auf derselben Origin; UNC-Pfade und fremde Schemata bleiben als
        // Text gespeichert und sind in der Anzeige nur nicht klickbar (SafeUrl.js)
        $link = $this->makeComponent('Link');
        $this->patchValue($link, ['text' => '/projects/12'])->assertOk();
        $this->assertSame(['text' => '/projects/12'], $this->storedData($link));

        $linkList = $this->makeComponent('LinkList');
        $this->patchValue($linkList, ['links' => [
            ['label' => 'Projekt', 'url' => '/projects/12?tab=3'],
            ['label' => 'Ablage', 'url' => '\\\\server\\share\\plan.pdf'],
            ['label' => 'Teams', 'url' => 'teams:/l/chat/0/0'],
        ]])->assertOk();
        $this->assertSame(['links' => [
            ['label' => 'Projekt', 'url' => '/projects/12?tab=3'],
            ['label' => 'Ablage', 'url' => '\\\\server\\share\\plan.pdf'],
            ['label' => 'Teams', 'url' => 'teams:/l/chat/0/0'],
        ]], $this->storedData($linkList));
    }

    #[Test]
    public function link_list_is_cleaned_and_limited(): void
    {
        $linkList = $this->makeComponent('LinkList', ['max_items' => 2]);

        $this->patchValue($linkList, ['links' => [
            ['label' => 'Presse', 'url' => 'https://a.test'],
            ['label' => '', 'url' => ''],
            ['label' => ' Nur Text ', 'url' => '', 'id' => 'client-id'],
        ]])->assertOk();
        $this->assertSame(['links' => [
            ['label' => 'Presse', 'url' => 'https://a.test'],
            ['label' => 'Nur Text', 'url' => ''],
        ]], $this->storedData($linkList));

        $this->patchValue($linkList, ['links' => [
            ['label' => 'A', 'url' => 'https://a.test'],
            ['label' => 'B', 'url' => 'https://b.test'],
            ['label' => 'C', 'url' => 'https://c.test'],
        ]])->assertUnprocessable();

        $this->patchValue($linkList, ['links' => [['label' => 'X', 'url' => 'javascript:alert(1)']]])
            ->assertUnprocessable();
    }

    #[Test]
    public function component_without_value_cannot_be_written(): void
    {
        $title = $this->makeComponent('Title');

        $this->patchValue($title, ['text' => 'egal'])->assertUnprocessable();
        $this->assertNull($this->storedData($title));
    }
}

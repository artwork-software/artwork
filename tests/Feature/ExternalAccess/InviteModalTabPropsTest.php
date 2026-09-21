<?php

namespace Tests\Feature\ExternalAccess;

use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use PHPUnit\Framework\Attributes\Test;

/**
 * Die Projekt-Tab-Seite liefert dem Einladungsdialog für Externe den Upload-Schalter und je Tab,
 * ob er eine Dokument-Komponente enthält (direkt oder innerhalb einer Disclosure).
 */
final class InviteModalTabPropsTest extends ExternalAccessTestCase
{
    private function makeComponent(ProjectTabComponentEnum $type, string $name): Component
    {
        return Component::create(['name' => $name, 'type' => $type->value, 'data' => []]);
    }

    private function attach(ProjectTab $tab, Component $component, int $order = 0): ComponentInTab
    {
        return ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => $order,
            'scope' => [$tab->id],
        ]);
    }

    /**
     * @return array{project: Project, plain: ProjectTab, documents: ProjectTab, disclosure: ProjectTab}
     */
    private function context(): array
    {
        $project = Project::factory()->create();

        $plain = ProjectTab::factory()->create(['order' => 1]);
        $this->attach($plain, $this->makeComponent(ProjectTabComponentEnum::TEXT_FIELD, 'Text'));

        $documents = ProjectTab::factory()->create(['order' => 2]);
        $this->attach($documents, $this->makeComponent(ProjectTabComponentEnum::TEXT_FIELD, 'Text'));
        $this->attach($documents, $this->makeComponent(ProjectTabComponentEnum::PROJECT_DOCUMENTS, 'Dokumente'), 1);

        $disclosure = ProjectTab::factory()->create(['order' => 3]);
        $disclosureComponent = $this->makeComponent(ProjectTabComponentEnum::DISCLOSURE_COMPONENT, 'Aufklapper');
        $this->attach($disclosure, $disclosureComponent);
        DisclosureComponents::create([
            'disclosure_id' => $disclosureComponent->id,
            'component_id' => $this->makeComponent(ProjectTabComponentEnum::PROJECT_DOCUMENTS, 'Dokumente')->id,
            'order' => 0,
            'scope' => [$disclosure->id],
        ]);

        return compact('project', 'plain', 'documents', 'disclosure');
    }

    /**
     * @return array<string, mixed>
     */
    private function tabPageProps(Project $project, ProjectTab $tab): array
    {
        $props = $this->get(route('projects.tab', ['project' => $project->id, 'projectTab' => $tab->id]))
            ->assertOk()
            ->getOriginalContent()
            ->getData()['page']['props'];

        return json_decode(json_encode($props), true);
    }

    /**
     * @param array<int, array<string, mixed>> $tabs
     */
    private function tabFlag(array $tabs, ProjectTab $tab): bool
    {
        foreach ($tabs as $entry) {
            if ((int) $entry['id'] === $tab->id) {
                $this->assertArrayHasKey('hasDocumentComponent', $entry);

                return $entry['hasDocumentComponent'];
            }
        }

        $this->fail("Tab {$tab->id} fehlt in headerObject.tabs");
    }

    #[Test]
    public function tabs_carry_has_document_component_flag(): void
    {
        $this->actingAsAdmin();
        $ctx = $this->context();

        $tabs = $this->tabPageProps($ctx['project'], $ctx['plain'])['headerObject']['tabs'];

        $this->assertFalse($this->tabFlag($tabs, $ctx['plain']));
        $this->assertTrue($this->tabFlag($tabs, $ctx['documents']));
        $this->assertTrue($this->tabFlag($tabs, $ctx['disclosure']));
    }

    #[Test]
    public function page_exposes_the_external_file_upload_flag(): void
    {
        $this->actingAsAdmin();
        $ctx = $this->context();

        $this->assertFalse($this->tabPageProps($ctx['project'], $ctx['plain'])['externalFileUploadEnabled']);

        $settings = app(ExternalAccessSettings::class);
        $settings->file_upload_enabled = true;
        $settings->save();

        $this->assertTrue($this->tabPageProps($ctx['project'], $ctx['plain'])['externalFileUploadEnabled']);
    }
}

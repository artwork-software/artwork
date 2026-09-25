<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\ExternalAccess\Services\ExternalComponentValueService;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\DisclosureComponents;
use Artwork\Modules\Project\Models\PrintLayoutComponents;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\ExternalAccess\CrmContactListFixtures;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class CrmContactListPrintAndDisclosureTest extends TestCase
{
    use CrmContactListFixtures;

    #[Test]
    public function crm_contact_list_is_printable_with_the_contacts_of_the_project(): void
    {
        $this->assertTrue(ProjectTabComponentEnum::CRM_CONTACT_LIST->isPrintable());

        $context = $this->crmContactListContext();
        $contact = $this->preLinkedContact($context, 'Mara Druck');
        $admin = $this->actingAsAdmin();
        $layout = ProjectPrintLayout::create([
            'name' => 'Abfrage',
            'description' => '',
            'columns_header' => 1,
            'columns_body' => 1,
            'columns_footer' => 1,
            'order' => 1,
            'user_id' => $admin->id,
            'notes' => ['header' => [], 'footer' => []],
        ]);
        PrintLayoutComponents::create([
            'project_print_layout_id' => $layout->id,
            'component_id' => $context['component']->id,
            'type' => 'body',
            'row' => 1,
            'position' => 1,
        ]);

        $this->get(route('project-print-layout.show', [$context['project']->id, $layout->id]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('project.crm_contact_lists.' . $context['component']->id . '.0.display_name', 'Mara Druck')
                ->where('project.crm_contact_lists.' . $context['component']->id . '.0.id', $contact->id));
    }

    #[Test]
    public function external_can_edit_components_inside_a_folder_of_the_shared_tab(): void
    {
        Event::fake([UpdateProjectComponentData::class]);
        $context = $this->crmContactListContext();
        $folder = Component::create(['name' => 'Ordner', 'type' => ProjectTabComponentEnum::DISCLOSURE_COMPONENT->value, 'data' => ['label' => 'Ordner']]);
        ComponentInTab::create(['project_tab_id' => $context['tab']->id, 'component_id' => $folder->id, 'order' => 5]);
        $field = Component::create(['name' => 'Im Ordner', 'type' => 'TextField', 'data' => ['label' => 'Im Ordner']]);
        DisclosureComponents::create(['disclosure_id' => $folder->id, 'component_id' => $field->id, 'order' => 0, 'scope' => []]);

        app(ExternalComponentValueService::class)->updateComponentValue(
            $context['external'],
            $context['project'],
            $context['tab'],
            $field,
            ['text' => 'aus dem Ordner'],
        );

        $this->assertSame(
            'aus dem Ordner',
            ProjectComponentValue::query()->where('component_id', $field->id)->where('project_id', $context['project']->id)->value('data')['text'] ?? null,
        );
    }
}

<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\DisclosureComponents;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Komponenten in Ordner (DisclosureComponent) legen: die Ordner-Regeln aus
 * ProjectTabComponentEnum gelten jetzt auch serverseitig, nicht nur im Drag & Drop.
 */
final class ProjectTabFolderPlacementTest extends FeatureTestCase
{
    private Component $folder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
        $this->folder = $this->tabComponent(ProjectTabComponentEnum::DISCLOSURE_COMPONENT);
    }

    private function tabComponent(ProjectTabComponentEnum $type): Component
    {
        return Component::factory()->create(['type' => $type->value, 'data' => []]);
    }

    /**
     * @return \Illuminate\Testing\TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function place(Component $component, ?Component $target = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('project-management-builder.add.disclosure.component'), [
            'component_id' => $component->id,
            'disclosure_id' => ($target ?? $this->folder)->id,
            'order' => 0,
        ]);
    }

    #[Test]
    public function a_regular_component_can_be_placed_in_a_folder(): void
    {
        $textField = $this->tabComponent(ProjectTabComponentEnum::TEXT_FIELD);

        $this->place($textField)->assertSuccessful();

        $this->assertTrue(DisclosureComponents::query()
            ->where('disclosure_id', $this->folder->id)
            ->where('component_id', $textField->id)
            ->exists());
    }

    #[Test]
    public function large_layout_components_and_folders_are_rejected(): void
    {
        $this->place($this->tabComponent(ProjectTabComponentEnum::CALENDAR))->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');
        $this->place($this->tabComponent(ProjectTabComponentEnum::DISCLOSURE_COMPONENT))->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');

        $this->assertSame(0, DisclosureComponents::query()->where('disclosure_id', $this->folder->id)->count());
    }

    #[Test]
    public function the_target_has_to_be_a_folder(): void
    {
        $notAFolder = $this->tabComponent(ProjectTabComponentEnum::TITLE);

        $this->place($this->tabComponent(ProjectTabComponentEnum::TEXT_FIELD), $notAFolder)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('disclosure_id');
    }

    #[Test]
    public function scoped_components_keep_their_scope(): void
    {
        $comments = $this->tabComponent(ProjectTabComponentEnum::COMMENT_TAB);

        $this->postJson(route('project-management-builder.add.disclosure.component.with.scopes'), [
            'component_id' => $comments->id,
            'disclosure_id' => $this->folder->id,
            'order' => 0,
            'scope' => [1, 2],
        ])->assertSuccessful();

        $this->assertTrue(DisclosureComponents::query()->where('component_id', $comments->id)->exists());
    }
}

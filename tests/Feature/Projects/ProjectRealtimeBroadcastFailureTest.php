<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Services\ExternalComponentValueService;
use Artwork\Modules\Project\Events\DeleteCommendInProject;
use Artwork\Modules\Project\Events\NewCommentInProject;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Nicht erreichbarer WebSocket-Server (Reverb): Kommentare und Komponentenwerte sind gespeichert, die
 * Antwort darf keine 500 sein – sonst setzen Checkbox/DropDown ihre Auswahl zurück und Nutzer:innen
 * speichern erneut.
 */
final class ProjectRealtimeBroadcastFailureTest extends FeatureTestCase
{
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();

        // Der Broadcast wirft wie das Pusher-SDK bei einem Verbindungsfehler
        $failBroadcast = static function (): void {
            throw new \RuntimeException('Pusher error: cURL error 7: Failed to connect to reverb port 8080.');
        };
        Event::listen(UpdateProjectComponentData::class, $failBroadcast);
        Event::listen(NewCommentInProject::class, $failBroadcast);
        Event::listen(DeleteCommendInProject::class, $failBroadcast);
    }

    #[Test]
    public function saving_a_component_value_succeeds_although_broadcasting_fails(): void
    {
        $this->actingAsAdmin();
        $component = $this->textField();

        $this->patchJson(
            route('project.tab.component.update', ['project' => $this->project->id, 'component' => $component->id]),
            ['data' => ['text' => 'gespeichert']]
        )->assertOk()->assertJsonPath('project_value.data.text', 'gespeichert');

        $this->assertSame(
            ['text' => 'gespeichert'],
            ProjectComponentValue::query()
                ->where('project_id', $this->project->id)
                ->where('component_id', $component->id)
                ->firstOrFail()
                ->data
        );
    }

    #[Test]
    public function storing_and_deleting_a_comment_succeed_although_broadcasting_fails(): void
    {
        $this->actingAsAdmin();

        $this->post(route('comments.store'), [
            'text' => 'Trotz Ausfall',
            'project_id' => $this->project->id,
        ])->assertSuccessful();

        $comment = Comment::query()->where('project_id', $this->project->id)->firstOrFail();
        $this->assertSame('Trotz Ausfall', $comment->text);

        $this->delete('/comments/' . $comment->id)->assertSuccessful();
        $this->assertFalse(Comment::withTrashed()->whereKey($comment->id)->exists());
    }

    #[Test]
    public function external_component_edits_succeed_although_broadcasting_fails(): void
    {
        $external = ExternalAccess::factory()->active()->create();
        $tab = ProjectTab::factory()->create();
        $component = $this->textField();
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => 0]);

        $value = app(ExternalComponentValueService::class)
            ->updateComponentValue($external, $this->project, $tab, $component, ['text' => 'extern']);

        $this->assertSame(['text' => 'extern'], $value->fresh()->data);
    }

    private function textField(): Component
    {
        return Component::create([
            'name' => 'Field ' . uniqid(),
            'type' => 'TextField',
            'data' => ['label' => 'Field', 'text' => '', 'placeholder' => ''],
        ]);
    }
}

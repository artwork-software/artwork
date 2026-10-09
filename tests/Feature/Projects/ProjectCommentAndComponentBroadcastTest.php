<?php

namespace Tests\Feature\Projects;

use Artwork\Modules\Project\Events\DeleteCommendInProject;
use Artwork\Modules\Project\Events\NewCommentInProject;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Comment;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\CommentService;
use Artwork\Modules\User\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Broadcasts auf project.{id} (Kanal prüft nur das Projekt-Sichtrecht) tragen nur Kennungen; Inhalte
 * holen die Clients über geprüfte Endpunkte. Dazu: Kommentare nur in sichtbaren Tabs anlegen.
 */
final class ProjectCommentAndComponentBroadcastTest extends FeatureTestCase
{
    private const SECRET_VALUE = 'TOP-SECRET-BROADCAST-VALUE';

    private Project $project;

    private ProjectTab $visibleTab;

    private ProjectTab $hiddenTab;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Project::factory()->create();
        $this->visibleTab = ProjectTab::factory()->create(['visible_for_all' => true]);
        $this->hiddenTab = ProjectTab::factory()->create(['visible_for_all' => false]);
    }

    #[Test]
    public function comment_broadcasts_carry_only_ids(): void
    {
        $comment = Comment::create([
            'text' => self::SECRET_VALUE,
            'project_id' => $this->project->id,
            'user_id' => User::factory()->create()->id,
            'tab_id' => $this->hiddenTab->id,
        ]);
        $expected = [
            'comment' => [
                'id' => $comment->id,
                'project_id' => $this->project->id,
                'tab_id' => $this->hiddenTab->id,
            ],
        ];

        $this->assertSame($expected, (new NewCommentInProject($comment, $this->project->id))->broadcastWith());
        $this->assertSame($expected, (new DeleteCommendInProject($comment, $this->project->id))->broadcastWith());
    }

    #[Test]
    public function component_value_broadcast_carries_only_ids(): void
    {
        $component = $this->createTextField('someSeeSomeEdit');
        $value = $this->setValue($component);

        $this->assertSame(
            ['data' => ['id' => $value->id, 'project_id' => $this->project->id, 'component_id' => $component->id]],
            (new UpdateProjectComponentData($value, $this->project->id))->broadcastWith()
        );
    }

    #[Test]
    public function reload_broadcasts_are_only_sent_after_commit(): void
    {
        // Clients laden auf das Event hin nach – vor dem Commit läsen sie den alten Stand
        foreach ([NewCommentInProject::class, DeleteCommendInProject::class, UpdateProjectComponentData::class] as $event) {
            $this->assertTrue(is_subclass_of($event, ShouldDispatchAfterCommit::class), $event);
        }
    }

    #[Test]
    public function comment_delete_is_broadcast_after_the_row_is_gone(): void
    {
        $comment = Comment::create([
            'text' => 'weg',
            'project_id' => $this->project->id,
            'user_id' => User::factory()->create()->id,
        ]);
        $existedAtBroadcast = null;
        Event::listen(DeleteCommendInProject::class, function (DeleteCommendInProject $event) use (&$existedAtBroadcast): void {
            $existedAtBroadcast = Comment::withTrashed()->whereKey($event->comment->id)->exists();
        });

        app(CommentService::class)->forceDelete($comment);

        $this->assertFalse($existedAtBroadcast);
    }

    #[Test]
    public function component_update_returns_the_saved_value_and_broadcasts_only_changes(): void
    {
        Event::fake([UpdateProjectComponentData::class]);
        $component = $this->createTextField();
        $this->placeInTab($this->visibleTab, $component);
        $this->actingAs($this->teamMember());
        $url = route('project.tab.component.update', ['project' => $this->project->id, 'component' => $component->id]);

        $this->patchJson($url, ['data' => ['text' => 'Erster Stand']])
            ->assertOk()
            ->assertJsonPath('project_value.component_id', $component->id)
            ->assertJsonPath('project_value.data.text', 'Erster Stand')
            ->assertJsonPath('project_value.text_without_html', 'Erster Stand');
        Event::assertDispatchedTimes(UpdateProjectComponentData::class, 1);

        // Fokuswechsel ohne Änderung: kein weiterer Broadcast (sonst Nachlade-Request je Betrachter)
        $this->patchJson($url, ['data' => ['text' => 'Erster Stand']])->assertOk();
        Event::assertDispatchedTimes(UpdateProjectComponentData::class, 1);

        $this->patchJson($url, ['data' => ['text' => 'Zweiter Stand']])->assertOk();
        Event::assertDispatchedTimes(UpdateProjectComponentData::class, 2);
    }

    #[Test]
    public function component_value_can_be_reloaded_by_people_who_see_the_component(): void
    {
        $component = $this->createTextField();
        $this->placeInTab($this->visibleTab, $component);
        $value = $this->setValue($component);
        $this->actingAs($this->teamMember());

        $this->getJson($this->valueUrl($component))
            ->assertOk()
            ->assertJsonPath('project_value.id', $value->id)
            ->assertJsonPath('project_value.data.text', self::SECRET_VALUE);
    }

    #[Test]
    public function component_value_of_restricted_components_is_forbidden(): void
    {
        $component = $this->createTextField('someSeeSomeEdit');
        $this->placeInTab($this->visibleTab, $component);
        $this->setValue($component);
        $this->actingAs($this->teamMember());

        $this->getJson($this->valueUrl($component))->assertForbidden();
    }

    #[Test]
    public function component_value_in_a_hidden_tab_is_forbidden(): void
    {
        $component = $this->createTextField();
        $this->placeInTab($this->hiddenTab, $component);
        $this->setValue($component);
        $this->actingAs($this->teamMember());

        $this->getJson($this->valueUrl($component))->assertForbidden();
    }

    #[Test]
    public function component_value_requires_project_view_rights(): void
    {
        $component = $this->createTextField();
        $this->placeInTab($this->visibleTab, $component);
        $this->setValue($component);
        $this->actingAsUserWith([]);

        $this->getJson($this->valueUrl($component))->assertForbidden();
    }

    #[Test]
    public function comments_can_only_be_stored_in_visible_existing_tabs(): void
    {
        $member = $this->teamMember();
        $this->actingAs($member);

        $this->post(route('comments.store'), [
            'text' => 'Blind',
            'project_id' => $this->project->id,
            'tab_id' => $this->hiddenTab->id,
        ])->assertSessionHasErrors('tab_id');

        $this->post(route('comments.store'), [
            'text' => 'Ins Leere',
            'project_id' => $this->project->id,
            'tab_id' => (int) ProjectTab::query()->max('id') + 1000,
        ])->assertSessionHasErrors('tab_id');

        $this->assertFalse(Comment::query()->where('project_id', $this->project->id)->exists());

        $this->post(route('comments.store'), [
            'text' => 'Sichtbar',
            'project_id' => $this->project->id,
            'tab_id' => $this->visibleTab->id,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Comment::query()
            ->where('project_id', $this->project->id)
            ->where('tab_id', $this->visibleTab->id)
            ->where('user_id', $member->id)
            ->exists());
    }

    #[Test]
    public function admins_may_comment_in_hidden_tabs(): void
    {
        $this->actingAsAdmin();

        $this->post(route('comments.store'), [
            'text' => 'Admin',
            'project_id' => $this->project->id,
            'tab_id' => $this->hiddenTab->id,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Comment::query()->where('tab_id', $this->hiddenTab->id)->exists());
    }

    private function teamMember(): User
    {
        $user = User::factory()->create();
        $this->project->users()->attach($user->id, ['can_write' => true]);

        return $user;
    }

    private function valueUrl(Component $component): string
    {
        return route('project.tab.component.value', ['project' => $this->project->id, 'component' => $component->id]);
    }

    private function createTextField(?string $permissionType = null): Component
    {
        return Component::create([
            'name' => 'Field ' . uniqid(),
            'type' => 'TextField',
            'data' => ['label' => 'Field', 'text' => '', 'placeholder' => ''],
            'permission_type' => $permissionType,
        ]);
    }

    private function setValue(Component $component): ProjectComponentValue
    {
        return ProjectComponentValue::create([
            'component_id' => $component->id,
            'project_id' => $this->project->id,
            'data' => ['text' => self::SECRET_VALUE],
        ]);
    }

    private function placeInTab(ProjectTab $tab, Component $component): ComponentInTab
    {
        return ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 1,
        ]);
    }
}

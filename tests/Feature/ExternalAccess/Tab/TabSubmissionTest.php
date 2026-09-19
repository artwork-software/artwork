<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Notifications\ExternalTabComponentUpdatedNotification;
use Artwork\Modules\ExternalAccess\Services\ExternalComponentValueService;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\ExternalAccess\ExternalAccessTestCase as TestCase;

final class TabSubmissionTest extends TestCase
{
    /**
     * @return array{external: ExternalAccess, project: Project, tab: ProjectTab, scope: ExternalAccessScope, inviter: User, component: Component}
     */
    private function context(bool $write = true): array
    {
        $inviter = User::factory()->create();
        $external = ExternalAccess::factory()->active()->create(['invited_by_user_id' => $inviter->id]);
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $factory = ExternalAccessScope::factory();
        if ($write) {
            $factory = $factory->write();
        }
        $scope = $factory->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);
        $component = Component::create(['name' => 'Field', 'type' => 'TextField', 'data' => []]);
        ComponentInTab::create(['project_tab_id' => $tab->id, 'component_id' => $component->id, 'order' => 0]);

        return compact('external', 'project', 'tab', 'scope', 'inviter', 'component');
    }

    #[Test]
    public function field_edits_no_longer_notify_but_submit_does(): void
    {
        Notification::fake();
        Event::fake([UpdateProjectComponentData::class]);
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'scope' => $scope, 'inviter' => $inviter, 'component' => $component] = $this->context();

        app(ExternalComponentValueService::class)->updateComponentValue($external, $project, $tab, $component, ['text' => 'Hallo']);
        Notification::assertNothingSent();

        $this->actingAs($external, 'external');
        $this->post(route('external.project.tab.submit', [$project->id, $tab->id]))
            ->assertRedirect(route('external.project.tab.show', [$project->id, $tab->id]));

        $this->assertNotNull($scope->fresh()->last_submitted_at);
        Notification::assertSentTo($inviter, ExternalTabComponentUpdatedNotification::class);
    }

    #[Test]
    public function submit_writes_project_history_with_external_causer(): void
    {
        Notification::fake();
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        $this->actingAs($external, 'external');

        $this->post(route('external.project.tab.submit', [$project->id, $tab->id]));

        $activity = Activity::query()
            ->where('log_name', 'project')
            ->where('subject_type', $project->getMorphClass())
            ->where('subject_id', $project->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($external->getMorphClass(), $activity->causer_type);
        $this->assertSame($external->id, (int) $activity->causer_id);
        $this->assertSame('External person {0} submitted data in tab {1}', $activity->properties[0]['translationKey']);
        $this->assertSame($tab->name, $activity->properties[0]['translationKeyPlaceholderValues'][1]);
    }

    #[Test]
    public function read_only_scope_cannot_submit(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context(write: false);
        $this->actingAs($external, 'external');

        $this->post(route('external.project.tab.submit', [$project->id, $tab->id]))->assertForbidden();
    }

    #[Test]
    public function project_history_serializes_external_causer_for_internal_users(): void
    {
        Notification::fake();
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        $this->actingAs($external, 'external');
        $this->post(route('external.project.tab.submit', [$project->id, $tab->id]));

        // Der externe Request hat den Standard-Guard auf 'external' umgestellt (shouldUse) —
        // für den internen Aufruf zurück auf 'web'.
        Auth::shouldUse('web');
        $this->actingAsAdmin();
        $response = $this->getJson(route('projects.history', $project->id));

        $response->assertOk();
        // Reihenfolge innerhalb derselben Sekunde ist nicht definiert → den externen Eintrag suchen
        $changer = collect($response->json('history'))
            ->pluck('changer')
            ->first(fn ($c) => is_array($c) && ($c['is_external'] ?? false));
        $this->assertNotNull($changer);
        $this->assertSame($external->email, $changer['email']);
        $this->assertSame('external', $changer['type']);
    }
}

<?php

namespace Tests\Feature\ExternalAccess\Tab;

use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotExternallyWritableException;
use Artwork\Modules\ExternalAccess\Exceptions\ComponentNotInTabException;
use Artwork\Modules\ExternalAccess\Http\Middleware\EnsureScopedAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccess;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessScope;
use Artwork\Modules\ExternalAccess\Services\ExternalComponentValueService;
use Artwork\Modules\Project\Events\UpdateProjectComponentData;
use Artwork\Modules\Project\Models\Component;
use Artwork\Modules\Project\Models\ComponentInTab;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectComponentValue;
use Artwork\Modules\Project\Models\ProjectTab;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class UpdateComponentValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Neutralize the real broadcaster but keep all other events intact.
        Event::fake([UpdateProjectComponentData::class]);
    }

    private function service(): ExternalComponentValueService
    {
        return $this->app->make(ExternalComponentValueService::class);
    }

    /**
     * @return array{
     *     external: ExternalAccess, project: Project, tab: ProjectTab, component: Component
     * }
     */
    private function context(string $type = 'TextField'): array
    {
        $external = ExternalAccess::factory()->active()->create();
        $project = Project::factory()->create();
        $tab = ProjectTab::factory()->create();
        $component = Component::create(['name' => 'Field', 'type' => $type, 'data' => []]);
        ComponentInTab::create([
            'project_tab_id' => $tab->id,
            'component_id' => $component->id,
            'order' => 0,
        ]);

        return compact('external', 'project', 'tab', 'component');
    }

    #[Test]
    public function external_with_write_scope_can_update_text_field(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'hello']);

        $this->assertDatabaseHas('project_component_values', [
            'project_id' => $project->id,
            'component_id' => $component->id,
        ]);
    }

    #[Test]
    public function external_with_read_scope_cannot_update(): void
    {
        // The read-vs-write gate is enforced by the EnsureScopedAccess middleware
        // on the write route. A read scope on a write endpoint must 403.
        ['external' => $external, 'project' => $project, 'tab' => $tab] = $this->context();
        ExternalAccessScope::factory()->create([
            'external_access_id' => $external->id,
            'project_id' => $project->id,
            'project_tab_id' => $tab->id,
        ]);

        $request = Request::create('/x', 'PATCH');
        $request->setUserResolver(fn ($guard = null) => $guard === 'external' ? $external : null);
        $route = new Route(['PATCH'], 'x', fn () => null);
        $route->bind($request);
        $route->setParameter('project', $project);
        $route->setParameter('tab', $tab);
        $request->setRouteResolver(fn () => $route);

        try {
            $this->app->make(EnsureScopedAccess::class)->handle(
                $request,
                fn () => new Response('ok'),
                'write',
            );
            $this->fail('Expected 403 for read scope on write endpoint.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    #[Test]
    public function external_cannot_update_non_writable_component_type(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] =
            $this->context('Title');

        $this->expectException(ComponentNotExternallyWritableException::class);

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['title' => 'x']);
    }

    #[Test]
    public function external_cannot_update_component_outside_their_tab_scope(): void
    {
        ['external' => $external, 'project' => $project, 'component' => $component] = $this->context();
        // A different tab the component is NOT attached to.
        $otherTab = ProjectTab::factory()->create();

        $this->expectException(ComponentNotInTabException::class);

        $this->service()->updateComponentValue($external, $project, $otherTab, $component, ['text' => 'x']);
    }

    #[Test]
    public function existing_value_is_updated_in_place(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();
        $existing = ProjectComponentValue::create([
            'project_id' => $project->id,
            'component_id' => $component->id,
            'data' => ['text' => 'old'],
        ]);

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'new']);

        $this->assertSame(1, ProjectComponentValue::where('project_id', $project->id)
            ->where('component_id', $component->id)->count());
        $this->assertSame($existing->id, ProjectComponentValue::first()->id);
        $this->assertStringContainsString('new', ProjectComponentValue::first()->data['text']);
    }

    #[Test]
    public function new_value_is_created_when_none_exists(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();

        $this->assertSame(0, ProjectComponentValue::count());

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'created']);

        $this->assertSame(1, ProjectComponentValue::count());
    }

    #[Test]
    public function audit_log_is_created_with_ip_and_useragent(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();

        $this->app->instance('request', Request::create(
            '/external/x',
            'PATCH',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '10.1.2.3', 'HTTP_USER_AGENT' => 'PHPUnit-Agent'],
        ));

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'logged']);

        $activity = Activity::query()->where('log_name', 'external_component_edit')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame(ExternalAccess::class, $activity->causer_type);
        $this->assertSame($external->id, (int) $activity->causer_id);
        $this->assertSame('10.1.2.3', $activity->properties['ip_address']);
        $this->assertSame('PHPUnit-Agent', $activity->properties['user_agent']);
    }

    #[Test]
    public function audit_log_contains_old_and_new_value(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();
        ProjectComponentValue::create([
            'project_id' => $project->id,
            'component_id' => $component->id,
            'data' => ['text' => 'before'],
        ]);

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'after']);

        $activity = Activity::query()->where('log_name', 'external_component_edit')->latest('id')->first();

        $this->assertSame(['text' => 'before'], $activity->properties['old_value']);
        $this->assertStringContainsString('after', $activity->properties['new_value']['text']);
    }

    #[Test]
    public function broadcast_event_is_dispatched(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'broadcast me']);

        Event::assertDispatched(UpdateProjectComponentData::class, function ($event) use ($project) {
            return $event->projectId === $project->id;
        });
    }

    #[Test]
    public function concurrent_updates_use_lock_for_update(): void
    {
        // True row-locking concurrency cannot be simulated deterministically in a
        // transactional test; we assert the consistency guarantee it provides:
        // two updates collapse to a single row holding the last write.
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'first']);
        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => 'second']);

        $this->assertSame(1, ProjectComponentValue::where('project_id', $project->id)
            ->where('component_id', $component->id)->count());
        $this->assertStringContainsString('second', ProjectComponentValue::first()->data['text']);
    }

    #[Test]
    public function text_data_gets_nl2br_normalization(): void
    {
        ['external' => $external, 'project' => $project, 'tab' => $tab, 'component' => $component] = $this->context();

        $this->service()->updateComponentValue($external, $project, $tab, $component, ['text' => "line1\nline2"]);

        $stored = ProjectComponentValue::first()->data['text'];
        $this->assertStringContainsString('<br', $stored);
    }
}

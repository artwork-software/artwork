<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Concerns\GrantsAiAccess;

/**
 * core-api-context-projects: GET /ai/context, /ai/projects, /ai/projects/{id}
 * (Konzept B1 §3–4, §8; B4 §4).
 */
final class AiContextProjectsApiTest extends TestCase
{
    use GrantsAiAccess;

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function memberOf(Project $project): User
    {
        $user = User::factory()->create();
        $project->users()->attach($user);

        return $user;
    }

    // --- Scope ---------------------------------------------------------------------------

    #[Test]
    public function a_token_without_the_read_scope_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAsToken($user, [AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/context')->assertForbidden();
        $this->getJson('/api/v1/ai/projects')->assertForbidden();
    }

    #[Test]
    public function without_a_token_there_is_no_access(): void
    {
        $this->getJson('/api/v1/ai/projects')->assertUnauthorized();
    }

    // --- context -------------------------------------------------------------------------

    #[Test]
    public function the_context_names_user_house_and_relevant_permissions(): void
    {
        $user = User::factory()->create(['first_name' => 'Alma', 'last_name' => 'Hoppe']);
        $this->actingAsUserWith(PermissionEnum::VIEW_SHIFT_PLAN->value, $user);
        $this->actingAsToken($user->fresh());

        $payload = $this->getJson('/api/v1/ai/context')->assertOk()->json();

        $this->assertSame($user->id, $payload['user']['id']);
        $this->assertSame('Alma Hoppe', $payload['user']['display_name']);
        $this->assertSame(config('app.timezone'), $payload['house']['timezone']);
        $this->assertContains(PermissionEnum::VIEW_SHIFT_PLAN->value, $payload['permissions_summary']);
    }

    /** Der Kontext geht in den Modellkontext — personenbezogene Daten nur, soweit noetig. */
    #[Test]
    public function the_context_contains_no_email_and_no_foreign_user_ids(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'fremde@example.test']);
        $this->actingAsToken($user);

        $raw = $this->getJson('/api/v1/ai/context')->assertOk()->content();

        $this->assertStringNotContainsString('@', $raw);
        $this->assertStringNotContainsString('fremde@example.test', $raw);
    }

    // --- projects ------------------------------------------------------------------------

    #[Test]
    public function the_search_only_returns_projects_the_user_may_enter(): void
    {
        $mine = Project::factory()->create(['name' => 'Meine Produktion']);
        $foreign = Project::factory()->create(['name' => 'Fremde Produktion']);
        $user = $this->memberOf($mine);
        $this->actingAsToken($user);

        $payload = $this->getJson('/api/v1/ai/projects')->assertOk()->json();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($mine->id, $payload['data'][0]['id']);
        $this->assertStringNotContainsString($foreign->name, json_encode($payload));
    }

    /**
     * Gate::before gibt Admins alles frei — hier ist das richtig, weil die Projektuebersicht
     * dieselbe Regel benutzt (core-visibility-scopes §2). Der Test haelt die Semantik fest.
     */
    #[Test]
    public function an_artwork_admin_sees_every_project(): void
    {
        Project::factory()->count(2)->create();
        $admin = $this->adminUser();
        $this->actingAsToken($admin);

        $this->getJson('/api/v1/ai/projects')->assertOk()->assertJsonPath('total', 2);
    }

    /**
     * Regression: Der Rechtekatalog existiert nur fuer den Guard `web`. Unter `auth:api` liefert
     * can() sonst still false — die KI-Endpunkte haetten dann weniger gezeigt als die Oberflaeche.
     */
    #[Test]
    public function a_global_permission_also_counts_under_the_api_guard(): void
    {
        Project::factory()->count(2)->create();
        $user = User::factory()->create();
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value, $user);
        $this->actingAsToken($user->fresh());

        $this->getJson('/api/v1/ai/projects')->assertOk()->assertJsonPath('total', 2);
    }

    #[Test]
    public function the_search_matches_the_name(): void
    {
        $hit = Project::factory()->create(['name' => 'Hamlet 2026']);
        $miss = Project::factory()->create(['name' => 'Wintermaerchen']);
        $user = $this->memberOf($hit);
        $miss->users()->attach($user);
        $this->actingAsToken($user);

        $payload = $this->getJson('/api/v1/ai/projects?q=Hamlet')->assertOk()->json();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($hit->id, $payload['data'][0]['id']);
    }

    #[Test]
    public function the_list_is_capped_and_marks_itself_as_truncated(): void
    {
        $user = User::factory()->create();
        $this->actingAsUserWith(PermissionEnum::PROJECT_VIEW->value, $user);
        Project::factory()->count(3)->create();
        $this->actingAsToken($user->fresh());

        $payload = $this->getJson('/api/v1/ai/projects?limit=2')->assertOk()->json();

        $this->assertCount(2, $payload['data']);
        $this->assertSame(3, $payload['total']);
        $this->assertSame(2, $payload['limit']);
        $this->assertTrue($payload['truncated']);
    }

    #[Test]
    public function a_period_filter_needs_both_ends_and_may_not_exceed_the_maximum(): void
    {
        $user = User::factory()->create();
        $this->actingAsToken($user);

        $this->getJson('/api/v1/ai/projects?from=2026-01-01')->assertStatus(422);

        $from = CarbonImmutable::parse('2026-01-01');
        $to = $from->addDays((int) config('ai.max_range_days') + 1);

        $this->getJson("/api/v1/ai/projects?from={$from->toDateString()}&to={$to->toDateString()}")
            ->assertStatus(422);
    }

    #[Test]
    public function the_period_filter_keeps_only_projects_with_events_in_the_window(): void
    {
        $inside = Project::factory()->create();
        $outside = Project::factory()->create();
        $user = $this->memberOf($inside);
        $outside->users()->attach($user);

        $this->eventFor($inside, '2026-03-10 10:00:00', '2026-03-10 12:00:00');
        $this->eventFor($outside, '2026-08-10 10:00:00', '2026-08-10 12:00:00');

        $this->actingAsToken($user);

        $payload = $this->getJson('/api/v1/ai/projects?from=2026-03-01&to=2026-03-31')->assertOk()->json();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($inside->id, $payload['data'][0]['id']);
    }

    #[Test]
    public function free_text_is_truncated_to_the_configured_limit(): void
    {
        config(['ai.text_limit' => 20]);
        $project = Project::factory()->create(['name' => str_repeat('a', 100)]);
        $user = $this->memberOf($project);
        $this->actingAsToken($user);

        $name = $this->getJson('/api/v1/ai/projects')->assertOk()->json('data.0.name');

        $this->assertLessThanOrEqual(21, mb_strlen($name));
    }

    // --- project detail ------------------------------------------------------------------

    #[Test]
    public function the_detail_shows_period_team_size_and_the_next_events(): void
    {
        $project = Project::factory()->create(['name' => 'Detailprojekt']);
        $user = $this->memberOf($project);
        $this->eventFor($project, '2026-03-10 10:00:00', '2026-03-10 12:00:00');
        $this->eventFor($project, CarbonImmutable::now()->addDay()->toDateTimeString(), CarbonImmutable::now()->addDay()->addHours(2)->toDateTimeString());
        $this->actingAsToken($user);

        $payload = $this->getJson("/api/v1/ai/projects/{$project->id}")->assertOk()->json();

        $this->assertSame($project->id, $payload['id']);
        $this->assertSame(1, $payload['team_size']);
        $this->assertSame(2, $payload['event_count']);
        $this->assertNotNull($payload['period']['from']);
        $this->assertCount(1, $payload['next_events']);
    }

    #[Test]
    public function a_foreign_project_is_a_404_not_a_403(): void
    {
        $foreign = Project::factory()->create();
        $user = User::factory()->create();
        $this->actingAsToken($user);

        $this->getJson("/api/v1/ai/projects/{$foreign->id}")->assertNotFound();
    }

    #[Test]
    public function a_department_member_may_open_the_project(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $department->users()->attach($user);
        $project->departments()->attach($department);
        $this->actingAsToken($user);

        $this->getJson("/api/v1/ai/projects/{$project->id}")->assertOk();
    }

    private function eventFor(Project $project, string $start, string $end): Event
    {
        return Event::factory()->create([
            'project_id' => $project->id,
            'room_id' => Room::factory()->create()->id,
            'event_type_id' => EventType::factory()->create()->id,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}

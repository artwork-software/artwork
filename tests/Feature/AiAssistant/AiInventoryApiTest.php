<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Concerns\GrantsAiAccess;

/**
 * core-api-inventory: GET /ai/inventory/availability (Konzept B1 §3, §8).
 */
final class AiInventoryApiTest extends TestCase
{
    use GrantsAiAccess;

    private const DAY = '2026-09-16';

    protected function setUp(): void
    {
        parent::setUp();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));
    }

    /** @param string[] $scopes */
    private function actingAsToken(User $user, array $scopes = [AiScopes::READ]): void
    {
        $this->app['auth']->forgetGuards();

        $token = $this->grantAiAccess($user)->createToken('ai-session-test', $scopes);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function range(): string
    {
        return 'from=' . self::DAY . '&to=' . self::DAY;
    }

    /** @param array<string, mixed> $attributes */
    private function article(array $attributes = []): InventoryArticle
    {
        return InventoryArticle::factory()->create(array_merge([
            'name' => 'Funkstrecke',
            'quantity' => 10,
        ], $attributes));
    }

    private function readyStatus(InventoryArticle $article, int $value): void
    {
        $status = InventoryArticleStatus::create(['name' => 'Einsatzbereit', 'color' => '#00ff00', 'order' => 1]);
        $status->default = true;
        $status->save();
        $article->statusValues()->attach($status->id, ['value' => $value]);
    }

    private function internalIssue(InventoryArticle $article, int $quantity, ?Project $project = null): InternalIssue
    {
        $issue = InternalIssue::factory()->create([
            'project_id' => $project?->id,
            'start_date' => self::DAY,
            'end_date' => self::DAY,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ]);
        $issue->articles()->attach($article->id, ['quantity' => $quantity]);

        return $issue;
    }

    private function memberOf(Project $project): User
    {
        $user = User::factory()->create();
        $project->users()->attach($user->id, ['access_budget' => false, 'is_manager' => false, 'can_write' => false]);

        return $user->fresh();
    }

    // --- Scope und Zeitraum ------------------------------------------------------------------

    #[Test]
    public function a_token_without_the_read_scope_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create(), [AiScopes::TRANSCRIPT]);

        $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())->assertForbidden();
    }

    #[Test]
    public function the_endpoint_demands_a_period(): void
    {
        $this->actingAsToken(User::factory()->create());

        $this->getJson('/api/v1/ai/inventory/availability')->assertStatus(422);
    }

    #[Test]
    public function a_period_longer_than_the_maximum_is_rejected(): void
    {
        $this->actingAsToken(User::factory()->create());

        $to = CarbonImmutable::parse(self::DAY)->addDays((int) config('ai.max_range_days') + 1);

        $this->getJson('/api/v1/ai/inventory/availability?from=' . self::DAY . '&to=' . $to->toDateString())
            ->assertStatus(422);
    }

    // --- Verfuegbarkeit ----------------------------------------------------------------------

    #[Test]
    public function the_ready_status_is_the_total_and_issues_reduce_the_availability(): void
    {
        $article = $this->article();
        $this->readyStatus($article, 4);
        $this->internalIssue($article, 3);

        $this->actingAsToken(User::factory()->create());

        $entry = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0');

        $this->assertSame($article->id, $entry['id']);
        $this->assertEqualsWithDelta(4.0, $entry['total'], 0.001);
        $this->assertEqualsWithDelta(3.0, $entry['reserved'], 0.001);
        $this->assertEqualsWithDelta(1.0, $entry['available'], 0.001);
    }

    #[Test]
    public function an_article_without_a_maintained_status_falls_back_to_its_quantity(): void
    {
        $article = $this->article(['quantity' => 7]);
        $this->internalIssue($article, 2);

        $this->actingAsToken(User::factory()->create());

        $entry = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0');

        $this->assertEqualsWithDelta(7.0, $entry['total'], 0.001);
        $this->assertEqualsWithDelta(5.0, $entry['available'], 0.001);
    }

    #[Test]
    public function issues_outside_the_period_do_not_count(): void
    {
        $article = $this->article();
        $this->readyStatus($article, 4);

        $issue = InternalIssue::factory()->create([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-02',
        ]);
        $issue->articles()->attach($article->id, ['quantity' => 3]);

        $this->actingAsToken(User::factory()->create());

        $entry = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0');

        $this->assertEqualsWithDelta(0.0, $entry['reserved'], 0.001);
        $this->assertEqualsWithDelta(4.0, $entry['available'], 0.001);
        $this->assertNull($entry['next_reservation']);
    }

    #[Test]
    public function an_external_loan_reduces_the_availability_without_a_project(): void
    {
        $article = $this->article();
        $this->readyStatus($article, 5);

        $issue = ExternalIssue::factory()->create([
            'project_id' => null,
            'issue_date' => self::DAY,
            'return_date' => self::DAY,
        ]);
        $issue->articles()->attach($article->id, ['quantity' => 2]);

        $this->actingAsToken(User::factory()->create());

        $entry = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0');

        $this->assertEqualsWithDelta(2.0, $entry['reserved'], 0.001);
        $this->assertNull($entry['next_reservation']['project']);
        $this->assertSame(2, $entry['next_reservation']['quantity']);
    }

    // --- Sichtbarkeit ------------------------------------------------------------------------

    #[Test]
    public function a_reservation_of_an_invisible_project_reduces_the_stock_without_its_name(): void
    {
        $article = $this->article();
        $this->readyStatus($article, 4);
        $project = Project::factory()->create(['name' => 'Geheimprojekt']);
        $this->internalIssue($article, 3, $project);

        $this->actingAsToken(User::factory()->create());

        $entry = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0');

        $this->assertEqualsWithDelta(3.0, $entry['reserved'], 0.001);
        $this->assertEqualsWithDelta(1.0, $entry['available'], 0.001);
        $this->assertNull($entry['next_reservation']['project']);
        $this->assertStringNotContainsString('Geheimprojekt', json_encode($entry, JSON_THROW_ON_ERROR));
    }

    #[Test]
    public function a_visible_project_is_named(): void
    {
        $article = $this->article();
        $this->readyStatus($article, 4);
        $project = Project::factory()->create(['name' => 'Tosca']);
        $this->internalIssue($article, 3, $project);

        $this->actingAsToken($this->memberOf($project));

        $entry = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0');

        $this->assertSame('Tosca', $entry['next_reservation']['project']['name']);
        $this->assertStringStartsWith(self::DAY, (string) $entry['next_reservation']['period']['from']);
    }

    #[Test]
    public function an_admin_sees_the_project_name_like_the_interface_does(): void
    {
        $article = $this->article();
        $this->readyStatus($article, 4);
        $project = Project::factory()->create(['name' => 'Tosca']);
        $this->internalIssue($article, 1, $project);

        $this->actingAsToken($this->adminUser()->fresh());

        $entry = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0');

        $this->assertSame('Tosca', $entry['next_reservation']['project']['name']);
    }

    // --- Liste -------------------------------------------------------------------------------

    #[Test]
    public function the_search_filters_by_name(): void
    {
        $wanted = $this->article(['name' => 'Funkstrecke A']);
        $this->article(['name' => 'Scheinwerfer']);

        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range() . '&q=funk')
            ->assertOk()
            ->json();

        $this->assertSame(1, $payload['total']);
        $this->assertSame($wanted->id, $payload['data'][0]['id']);
    }

    #[Test]
    public function the_list_honours_the_limit(): void
    {
        $this->article(['name' => 'A']);
        $this->article(['name' => 'B']);
        $this->article(['name' => 'C']);

        $this->actingAsToken(User::factory()->create());

        $payload = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range() . '&limit=2')
            ->assertOk()
            ->json();

        $this->assertSame(3, $payload['total']);
        $this->assertCount(2, $payload['data']);
        $this->assertTrue($payload['truncated']);
    }

    #[Test]
    public function free_text_is_truncated_to_the_configured_limit(): void
    {
        config(['ai.text_limit' => 20]);
        $this->article(['name' => str_repeat('a', 100)]);

        $this->actingAsToken(User::factory()->create());

        $name = $this->getJson('/api/v1/ai/inventory/availability?' . $this->range())
            ->assertOk()
            ->json('data.0.name');

        $this->assertLessThanOrEqual(21, mb_strlen((string) $name));
    }
}

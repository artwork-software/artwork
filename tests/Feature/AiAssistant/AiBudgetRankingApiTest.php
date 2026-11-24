<?php

namespace Tests\Feature\AiAssistant;

use Artwork\Modules\AiAssistant\Support\AiScopes;
use Artwork\Modules\Budget\Models\Column;
use Artwork\Modules\Budget\Models\ColumnCell;
use Artwork\Modules\Budget\Models\MainPosition;
use Artwork\Modules\Budget\Models\SubPosition;
use Artwork\Modules\Budget\Models\SubPositionRow;
use Artwork\Modules\Budget\Models\Table;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\GrantsAiAccess;
use Tests\TestCase;

/**
 * core-ai-aggregates: GET /ai/projects/budget-ranking — Projekte nach Einnahmen, Kosten oder Ergebnis
 * einer Budgetspalte (Abrechnungszeitraum) vergleichen (Entgrenzen P1).
 */
final class AiBudgetRankingApiTest extends TestCase
{
    use GrantsAiAccess;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableAiAssistant();

        app(ClientRepository::class)
            ->createPersonalAccessGrantClient('PAT client', config('auth.guards.api.provider'));

        $this->user = User::factory()->create();
        $token = $this->grantAiAccess($this->user)->createToken('ai-session-test', [AiScopes::READ]);
        $this->withHeader('Authorization', 'Bearer ' . $token->accessToken);
    }

    private function project(string $name, bool $budgetAccess = true): Project
    {
        $project = Project::factory()->create(['name' => $name, 'is_group' => false]);
        $project->users()->attach($this->user, ['access_budget' => $budgetAccess]);

        return $project;
    }

    /**
     * Budgettabelle: drei Strukturspalten, danach je Wertspalte eine Kosten- und eine Erloeszeile.
     *
     * @param list<array{name: string, sub?: string, relevant?: bool, cost?: string, earning?: string}> $valueColumns
     */
    private function budget(Project $project, array $valueColumns): void
    {
        $table = Table::query()->create(['project_id' => $project->id, 'name' => 'Budget', 'is_template' => false]);
        $columns = [];
        foreach (['KTO', 'KST', 'Position'] as $position => $name) {
            $columns[] = [Column::query()->create([
                'table_id' => $table->id, 'name' => $name, 'subName' => '', 'type' => 'empty',
                'position' => $position, 'relevant_for_project_groups' => false,
            ]), null];
        }
        foreach ($valueColumns as $index => $spec) {
            $columns[] = [Column::query()->create([
                'table_id' => $table->id,
                'name' => $spec['name'],
                'subName' => $spec['sub'] ?? '',
                'type' => 'empty',
                'position' => 3 + $index,
                'relevant_for_project_groups' => $spec['relevant'] ?? false,
            ]), $spec];
        }

        foreach (['BUDGET_TYPE_COST' => 'cost', 'BUDGET_TYPE_EARNING' => 'earning'] as $type => $key) {
            $main = MainPosition::query()->create([
                'table_id' => $table->id, 'type' => $type, 'name' => $key, 'position' => $key === 'cost' ? 0 : 1,
            ]);
            $sub = SubPosition::query()->create(['main_position_id' => $main->id, 'name' => $key, 'position' => 0]);
            $row = SubPositionRow::query()->create([
                'sub_position_id' => $sub->id, 'commented' => false, 'position' => 0, 'order' => 0,
            ]);
            foreach ($columns as [$column, $spec]) {
                ColumnCell::query()->create([
                    'column_id' => $column->id,
                    'sub_position_row_id' => $row->id,
                    'value' => $spec === null ? '' : ($spec[$key] ?? '0'),
                ]);
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private function names(array $payload): array
    {
        return array_column(array_column($payload['projects'], 'project'), 'name');
    }

    /** @return array<string, mixed> */
    private function ranking(string $query = ''): array
    {
        return $this->getJson('/api/v1/ai/projects/budget-ranking' . ($query === '' ? '' : '?' . $query))
            ->assertOk()
            ->json();
    }

    #[Test]
    public function budget_ranking_sorts_by_earnings_desc(): void
    {
        $this->budget($this->project('Faust'), [['name' => 'Plan', 'cost' => '100', 'earning' => '500']]);
        $this->budget($this->project('Möwe'), [['name' => 'Plan', 'cost' => '6822', 'earning' => '6500']]);
        $this->budget($this->project('Lear'), [['name' => 'Plan', 'cost' => '10', 'earning' => '70']]);

        $payload = $this->ranking();

        $this->assertSame(['Möwe', 'Faust', 'Lear'], $this->names($payload));
        $this->assertSame('6500.00', $payload['projects'][0]['earnings']);
        $this->assertSame('6822.00', $payload['projects'][0]['costs']);
        $this->assertSame('-322.00', $payload['projects'][0]['result']);
        $this->assertSame('Plan', $payload['projects'][0]['column']['name']);
        $this->assertSame('earnings', $payload['sort']);
        $this->assertNull($payload['period']);
        $this->assertSame(3, $payload['total']);
        $this->assertFalse($payload['truncated']);

        $byResult = $this->ranking('sort=result&direction=asc&limit=2');
        $this->assertSame(['Möwe', 'Lear'], $this->names($byResult));
        $this->assertTrue($byResult['truncated']);
    }

    #[Test]
    public function budget_ranking_picks_period_column_by_token(): void
    {
        $this->budget($this->project('Faust'), [
            ['name' => '2025', 'sub' => '€', 'cost' => '1', 'earning' => '900'],
            ['name' => 'Plan 2026', 'sub' => '€', 'cost' => '1', 'earning' => '300'],
        ]);
        $this->budget($this->project('Möwe'), [
            ['name' => '20260', 'cost' => '1', 'earning' => '999'],
            ['name' => 'Soll', 'sub' => '2026 €', 'cost' => '1', 'earning' => '200'],
        ]);

        $payload = $this->ranking('period=2026');

        $this->assertSame('2026', $payload['period']);
        $this->assertSame(['Faust', 'Möwe'], $this->names($payload));
        $this->assertSame('Plan 2026', $payload['projects'][0]['column']['name']);
        $this->assertSame('300.00', $payload['projects'][0]['earnings']);
        $this->assertSame('2026 €', $payload['projects'][1]['column']['sub_name']);
    }

    #[Test]
    public function budget_ranking_prefers_relevant_column_on_multiple_matches(): void
    {
        $this->budget($this->project('Faust'), [
            ['name' => '2026 A', 'relevant' => true, 'earning' => '100'],
            ['name' => '2026 B', 'earning' => '200'],
        ]);
        $this->budget($this->project('Möwe'), [
            ['name' => '2026 A', 'earning' => '100'],
            ['name' => '2026 B', 'earning' => '50'],
        ]);

        $columns = collect($this->ranking('period=2026')['projects'])->mapWithKeys(
            fn (array $entry) => [$entry['project']['name'] => $entry['column']['name']]
        );

        $this->assertSame('2026 A', $columns['Faust']);
        // Ohne Flag: die hinterste passende Spalte
        $this->assertSame('2026 B', $columns['Möwe']);
    }

    #[Test]
    public function budget_ranking_lists_projects_without_period_column(): void
    {
        $this->budget($this->project('Faust'), [['name' => '2026', 'earning' => '100']]);
        $old = $this->project('Anleitung');
        $this->budget($old, [
            ['name' => '2025', 'sub' => 'A', 'earning' => '500'],
            ['name' => '2025 €', 'earning' => '70'],
        ]);

        $payload = $this->ranking('period=2026');

        $this->assertSame(['Faust'], $this->names($payload));
        $this->assertSame(
            [['id' => $old->id, 'name' => 'Anleitung', 'columns' => ['2025 A', '2025 €']]],
            $payload['without_period_column']
        );
    }

    #[Test]
    public function budget_ranking_counts_without_access_and_lists_without_budget(): void
    {
        $this->budget($this->project('Faust'), [['name' => 'Plan', 'earning' => '100']]);
        $noAccess = $this->project('Geheim', budgetAccess: false);
        $this->budget($noAccess, [['name' => 'Plan', 'earning' => '1000000']]);
        $empty = $this->project('Externe Nutzungen');
        // Unsichtbares Projekt zaehlt nirgends
        $this->budget(Project::factory()->create(['name' => 'Fremd']), [['name' => 'Plan', 'earning' => '5']]);

        $response = $this->getJson('/api/v1/ai/projects/budget-ranking')->assertOk();
        $payload = $response->json();

        $this->assertSame(['Faust'], $this->names($payload));
        $this->assertSame(1, $payload['without_access']);
        $this->assertSame([['id' => $empty->id, 'name' => 'Externe Nutzungen']], $payload['without_budget']);
        $this->assertStringNotContainsString('Geheim', $response->getContent());
        $this->assertStringNotContainsString('Fremd', $response->getContent());
    }

    #[Test]
    public function budget_ranking_without_period_uses_relevant_column(): void
    {
        $this->budget($this->project('Faust'), [
            ['name' => 'Ist', 'relevant' => true, 'earning' => '100'],
            ['name' => 'Prognose', 'earning' => '999'],
        ]);

        $payload = $this->ranking();

        $this->assertSame('Ist', $payload['projects'][0]['column']['name']);
        $this->assertSame('100.00', $payload['projects'][0]['earnings']);
    }

    #[Test]
    public function budget_ranking_skips_groups_unless_requested(): void
    {
        $this->budget($this->project('Faust'), [['name' => 'Plan', 'earning' => '100']]);
        $group = $this->project('Spielzeit');
        $group->update(['is_group' => true]);
        $this->budget($group, [['name' => 'Plan', 'earning' => '100']]);

        $this->assertSame(['Faust'], $this->names($this->ranking()));

        $withGroups = $this->ranking('include_groups=1');
        $this->assertCount(2, $withGroups['projects']);
        $group = collect($withGroups['projects'])->firstWhere('project.name', 'Spielzeit');
        $this->assertTrue($group['project']['is_group']);
    }

    #[Test]
    public function budget_ranking_validates_input(): void
    {
        $this->getJson('/api/v1/ai/projects/budget-ranking?sort=profit')->assertStatus(422);
        $this->getJson('/api/v1/ai/projects/budget-ranking?limit=501')->assertStatus(422);
        $this->getJson('/api/v1/ai/projects/budget-ranking?period=' . str_repeat('x', 21))->assertStatus(422);
    }

    #[Test]
    public function budget_ranking_no_n_plus_one(): void
    {
        $count = function (int $projects): int {
            for ($i = 0; $i < $projects; $i++) {
                $this->budget($this->project("P{$i}-" . $projects), [['name' => '2026', 'earning' => (string) $i]]);
            }
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->ranking('period=2026');
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $five = $count(5);
        $twenty = $count(15);

        $this->assertLessThanOrEqual($five + 2, $twenty, "5 Projekte: {$five} Queries, 20 Projekte: {$twenty}");
    }
}

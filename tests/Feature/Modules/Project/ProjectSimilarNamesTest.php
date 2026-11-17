<?php

namespace Tests\Feature\Modules\Project;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Services\ProjectNameSimilarityService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Hinweis im Anlage-Modal auf Projekte und Projektgruppen mit gleichem oder ähnlichem Namen
 * (Route projects.similar-names).
 */
final class ProjectSimilarNamesTest extends FeatureTestCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function matchesFor(string $name): array
    {
        return $this->getJson(route('projects.similar-names', ['name' => $name]))
            ->assertOk()
            ->json('matches');
    }

    #[Test]
    public function finds_projects_and_groups_with_the_same_name_regardless_of_case_and_spacing(): void
    {
        $this->actingAsAdmin();
        $project = Project::factory()->create(['name' => 'Faust II']);
        $group = Project::factory()->create(['name' => 'faust  ii', 'is_group' => true]);
        Project::factory()->create(['name' => 'Woyzeck']);

        $matches = collect($this->matchesFor('  FAUST II '))->keyBy('id');

        $this->assertSame([$project->id, $group->id], $matches->keys()->sort()->values()->all());
        $this->assertSame('identical', $matches[$project->id]['match']);
        $this->assertFalse($matches[$project->id]['is_group']);
        $this->assertSame('identical', $matches[$group->id]['match']);
        $this->assertTrue($matches[$group->id]['is_group']);
    }

    #[Test]
    public function finds_similar_names_and_lists_identical_ones_first(): void
    {
        $this->actingAsAdmin();
        Project::factory()->create(['name' => 'Hamlet Gastspiel']);
        Project::factory()->create(['name' => 'Hamlett']);
        Project::factory()->create(['name' => 'Hamlet']);
        Project::factory()->create(['name' => 'Hamlet – Wiederaufnahme 2027']);
        Project::factory()->create(['name' => 'Die Räuber']);

        $matches = $this->matchesFor('Hamlet');

        $this->assertSame('Hamlet', $matches[0]['name']);
        $this->assertSame('identical', $matches[0]['match']);
        $this->assertEqualsCanonicalizing(
            ['Hamlet', 'Hamlett', 'Hamlet Gastspiel', 'Hamlet – Wiederaufnahme 2027'],
            array_column($matches, 'name')
        );
        $this->assertSame(['similar'], array_values(array_unique(array_column(array_slice($matches, 1), 'match'))));
    }

    #[Test]
    public function ignores_projects_in_the_trash(): void
    {
        $this->actingAsAdmin();
        Project::factory()->create(['name' => 'Sommerfest'])->delete();

        $this->assertSame([], $this->matchesFor('Sommerfest'));
    }

    #[Test]
    public function returns_at_most_ten_matches(): void
    {
        $this->actingAsAdmin();
        foreach (range(1, 12) as $number) {
            Project::factory()->create(['name' => 'Tanzfestival Teil ' . $number]);
        }

        $this->assertCount(10, $this->matchesFor('Tanzfestival'));
    }

    #[Test]
    public function links_only_projects_the_user_may_open(): void
    {
        ProjectTab::factory()->create(['default' => true, 'visible_for_all' => true]);
        $user = $this->actingAsUserWith(PermissionEnum::ADD_EDIT_OWN_PROJECT->value);
        $foreignProject = Project::factory()->create(['name' => 'Kirschgarten']);
        $ownProject = Project::factory()->create(['name' => 'Kirschgarten Probe']);
        $ownProject->users()->attach($user->id);

        $matches = collect($this->matchesFor('Kirschgarten'))->keyBy('id');

        $this->assertNull($matches[$foreignProject->id]['url']);
        $this->assertStringContainsString('/projects/' . $ownProject->id . '/tab/', $matches[$ownProject->id]['url']);
    }

    #[Test]
    public function requires_the_permission_to_create_projects(): void
    {
        $this->actingAsUserWith([]);

        $this->getJson(route('projects.similar-names', ['name' => 'Faust']))->assertForbidden();
    }

    #[Test]
    public function requires_a_name(): void
    {
        $this->actingAsAdmin();

        $this->getJson(route('projects.similar-names'))->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function namePairs(): array
    {
        return [
            'umlaut spelled out' => ['Die Räuber', 'Die Raeuber', true],
            'punctuation only' => ['Faust: Der Tragödie zweiter Teil', 'Faust Der Tragoedie zweiter Teil', true],
            'missing space' => ['Faust 2', 'Faust2', true],
            'one typo' => ['Woyzeck', 'Woyzek', true],
            'year changed' => ['Sommerfest 2026', 'Sommerfest 2025', true],
            'shared distinctive word' => ['Hamlet Premiere', 'Hamlet Gastspiel', true],
            'only the year in common' => ['Sommerfest 2026', 'Weihnachtsgala 2026', false],
            'only a short word in common' => ['Tanz im August', 'Tanz der Vampire', false],
            'only stop words in common' => ['Der Sturm', 'Der Besuch', false],
            'short name inside another' => ['Die', 'Die Räuber', false],
            'unrelated' => ['Woyzeck', 'Kirschgarten', false],
        ];
    }

    #[Test]
    #[DataProvider('namePairs')]
    public function decides_which_names_count_as_similar(string $name, string $candidate, bool $expected): void
    {
        $service = app(ProjectNameSimilarityService::class);

        $score = $service->score($service->normalize($name), $service->normalize($candidate));

        $this->assertSame($expected, $score !== null, sprintf('%s / %s => %s', $name, $candidate, var_export($score, true)));
    }
}

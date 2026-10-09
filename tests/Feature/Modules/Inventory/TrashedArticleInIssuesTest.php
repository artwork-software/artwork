<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Entscheidung 05.10.2026: Artikel mit künftigen Ausgaben dürfen mit Warnung in den Papierkorb,
 * die Ausgaben behalten den Artikel. Vorher verschwand er aus der Ausgabe und das nächste
 * Speichern löste die Verknüpfung endgültig.
 */
final class TrashedArticleInIssuesTest extends FeatureTestCase
{
    private function upcomingIssueWith(InventoryArticle $article): InternalIssue
    {
        $start = Carbon::today()->addDays(3);
        $issue = InternalIssue::factory()->create([
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
        ]);
        $issue->articles()->attach($article->id, ['quantity' => 2]);

        return $issue;
    }

    #[Test]
    public function the_delete_dialog_gets_the_number_of_upcoming_issues(): void
    {
        $this->actingAsAdmin();
        $article = InventoryArticle::factory()->create();
        $this->upcomingIssueWith($article);
        $past = InternalIssue::factory()->create(['start_date' => '2020-01-01', 'end_date' => '2020-01-02']);
        $past->articles()->attach($article->id, ['quantity' => 1]);

        $this->getJson(route('articles.future-issues', $article))
            ->assertOk()
            ->assertJsonPath('count', 1);
    }

    #[Test]
    public function an_issue_keeps_a_trashed_article_and_can_still_be_saved(): void
    {
        $this->actingAsAdmin();
        $article = InventoryArticle::factory()->create(['quantity' => 5]);
        $issue = $this->upcomingIssueWith($article);

        $this->delete(route('articles.destroy', $article))->assertSuccessful();
        $this->assertSoftDeleted($article);

        $this->assertSame([$article->id], $issue->fresh()->articles->pluck('id')->all());

        $this->patch(route('issue-of-material.update', $issue), [
            'id' => $issue->id,
            'name' => $issue->name,
            'start_date' => $issue->start_date->toDateString(),
            'start_time' => '08:00',
            'end_date' => $issue->end_date->toDateString(),
            'end_time' => '16:00',
            'articles' => [['id' => $article->id, 'quantity' => 2]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $issue->fresh()->articles()->count());
    }

    #[Test]
    public function copying_an_issue_skips_trashed_articles(): void
    {
        $this->actingAsAdmin();
        $kept = InventoryArticle::factory()->create();
        $trashed = InventoryArticle::factory()->create();
        $issue = $this->upcomingIssueWith($kept);
        $issue->articles()->attach($trashed->id, ['quantity' => 1]);
        $trashed->delete();

        // sonst lehnt das Speichern der Kopie den Papierkorb-Artikel mit 422 ab
        $copy = collect($this->getJson(route('issue-of-material.search-for-copy', ['q' => $issue->name]))
            ->assertOk()
            ->json())->firstWhere('id', $issue->id);

        $this->assertSame([$kept->id], collect($copy['articles'])->pluck('id')->all());
        $this->assertSame([$kept->id, $trashed->id], $issue->fresh()->articles->pluck('id')->sort()->values()->all());
    }
}

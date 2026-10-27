<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\ExternalIssue\Models\ExternalIssue;
use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Materialausgabe-Buch: die Artikel-$appends (Raum, Hersteller, Kategorie) dürfen keine Abfrage je
 * Artikel auslösen.
 */
final class IssueOfMaterialListQueriesTest extends FeatureTestCase
{
    #[Test]
    public function internal_issue_list_does_not_query_per_article(): void
    {
        $this->actingAsAdmin();
        // erster Aufruf legt die Einstellungen der Person an – nicht mitzählen
        $this->get(route('issue-of-material.index'))->assertOk();
        $issue = $this->issueWithArticles(2);
        $fewArticles = $this->countQueries(fn () => $this->get(route('issue-of-material.index'))->assertOk());

        $this->attachArticles($issue, 6);
        $manyArticles = $this->countQueries(fn () => $this->get(route('issue-of-material.index'))->assertOk());

        // kein Wachstum je Artikel (Einstellungs-Cache kann den zweiten Aufruf sogar kürzer machen)
        $this->assertLessThanOrEqual($fewArticles, $manyArticles);
    }

    #[Test]
    public function external_issue_list_does_not_query_per_article(): void
    {
        $this->actingAsAdmin();
        $this->get(route('extern-issue-of-material.index'))->assertOk();
        $issue = $this->issueWithArticles(2, ExternalIssue::class);
        $fewArticles = $this->countQueries(fn () => $this->get(route('extern-issue-of-material.index'))->assertOk());

        $this->attachArticles($issue, 6);
        $manyArticles = $this->countQueries(fn () => $this->get(route('extern-issue-of-material.index'))->assertOk());

        // kein Wachstum je Artikel (Einstellungs-Cache kann den zweiten Aufruf sogar kürzer machen)
        $this->assertLessThanOrEqual($fewArticles, $manyArticles);
    }

    /**
     * @param class-string<InternalIssue|ExternalIssue> $issueClass
     */
    private function issueWithArticles(int $count, string $issueClass = InternalIssue::class): InternalIssue|ExternalIssue
    {
        $issue = $issueClass::factory()->create();
        $this->attachArticles($issue, $count);

        return $issue;
    }

    private function attachArticles(InternalIssue|ExternalIssue $issue, int $count): void
    {
        foreach (InventoryArticle::factory()->count($count)->create() as $article) {
            $issue->articles()->attach($article->id, ['quantity' => 1]);
        }
    }

    private function countQueries(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $request();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}

<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class MaterialIssueValidationTest extends FeatureTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $article = InventoryArticle::factory()->create(['quantity' => 5]);

        return array_merge([
            'name' => 'Ausgabe Probebühne',
            'start_date' => '2026-10-10',
            'start_time' => '10:00',
            'end_date' => '2026-10-10',
            'end_time' => '12:00',
            'articles' => [['id' => $article->id, 'quantity' => 1]],
        ], $overrides);
    }

    #[Test]
    public function an_internal_issue_must_end_after_it_starts(): void
    {
        $this->actingAsAdmin();

        // Gleicher Tag, Ende vor Beginn: vorher gespeichert und von Verfügbarkeit/Planung ignoriert
        $this->post(route('issue-of-material.store'), $this->payload(['end_time' => '09:00']))
            ->assertSessionHasErrors('end_time');
        $this->post(route('issue-of-material.store'), $this->payload([
            'end_date' => '2026-10-09',
            'end_time' => '23:00',
        ]))->assertSessionHasErrors('end_time');

        $this->assertDatabaseMissing('internal_issues', ['name' => 'Ausgabe Probebühne']);
    }

    #[Test]
    public function the_same_article_cannot_appear_twice_in_one_issue(): void
    {
        $this->actingAsAdmin();
        $payload = $this->payload();
        $payload['articles'][] = ['id' => $payload['articles'][0]['id'], 'quantity' => 2];

        $this->post(route('issue-of-material.store'), $payload)
            ->assertSessionHasErrors('articles.1.id');
    }
}

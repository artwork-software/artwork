<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\InternalIssue\Models\InternalIssue;
use Artwork\Modules\Inventory\Models\InventoryArticle;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Das Abfragefenster mit Uhrzeit endet exklusiv: eine Ausgabe 10:00–14:00 überschneidet sich nicht
 * mit einer bestehenden ab 14:00. Vorher setzte der Batch-Endpunkt das Ende auf 14:00:59 und das
 * Modell rechnete +1 s → 14:01, das Intern-Modal zeigte „überbucht“.
 */
final class InventoryAvailabilityWindowTest extends FeatureTestCase
{
    private InventoryArticle $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();

        // ohne Statusmengen: einsatzbereit = Gesamtmenge
        $this->article = InventoryArticle::factory()->create(['quantity' => 5, 'is_detailed_quantity' => false]);
        $afternoon = InternalIssue::factory()->create([
            'start_date' => '2026-11-10',
            'start_time' => '14:00',
            'end_date' => '2026-11-10',
            'end_time' => '18:00',
        ]);
        $afternoon->articles()->attach($this->article->id, ['quantity' => 5]);
    }

    /**
     * @param array<string, string> $times
     * @return array<string, mixed>
     */
    private function batchStock(array $times = []): array
    {
        return $this->postJson(route('inventory.articles.available-stock.batch'), [
            'article_ids' => [$this->article->id],
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'type' => 'intern',
            ...$times,
        ])->assertOk()->json('data.' . $this->article->id);
    }

    #[Test]
    public function an_issue_ending_when_the_next_one_starts_is_not_overbooked(): void
    {
        $stock = $this->batchStock(['start_time' => '10:00', 'end_time' => '14:00']);

        $this->assertSame(0, $stock['reserved']);
        $this->assertEquals(5, $stock['available']);
    }

    #[Test]
    public function a_real_overlap_with_times_still_counts(): void
    {
        $stock = $this->batchStock(['start_time' => '10:00', 'end_time' => '14:30']);

        $this->assertSame(5, $stock['reserved']);
        $this->assertEquals(0, $stock['available']);
    }

    #[Test]
    public function a_date_only_window_still_covers_the_whole_day(): void
    {
        $stock = $this->batchStock();

        $this->assertSame(5, $stock['reserved']);
        $this->assertEquals(0, $stock['available']);
    }

    #[Test]
    public function the_model_treats_a_window_with_time_as_end_exclusive(): void
    {
        $article = $this->article->fresh();

        $this->assertSame(0, $article->getAvailableStock('2026-11-10 10:00:00', '2026-11-10 14:00:00')['reserved']);
        $this->assertSame(5, $this->article->fresh()->getAvailableStock('2026-11-10 10:00:00', '2026-11-10 14:00:01')['reserved']);
        // „23:59:59“ steht weiterhin für „bis Tagesende“
        $this->assertSame(5, $this->article->fresh()->getAvailableStock('2026-11-10 00:00:00', '2026-11-10 23:59:59')['reserved']);
    }
}

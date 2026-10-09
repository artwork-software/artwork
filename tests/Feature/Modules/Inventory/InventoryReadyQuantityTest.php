<?php

namespace Tests\Feature\Modules\Inventory;

use Artwork\Modules\Inventory\Models\InventoryArticle;
use Artwork\Modules\Inventory\Models\InventoryArticleStatus;
use Artwork\Modules\Inventory\Models\InventoryDetailedQuantityArticle;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Verfügbar ist die Menge im Status mit default-Flag. Vorher wurde der Name „Einsatzbereit“
 * verglichen – nach einer Umbenennung in den Einstellungen fiel die Verfügbarkeit überall auf 0.
 */
final class InventoryReadyQuantityTest extends FeatureTestCase
{
    private function createStatus(string $name, bool $default = false): InventoryArticleStatus
    {
        $status = new InventoryArticleStatus(['name' => $name, 'color' => '#000000', 'order' => 1]);
        $status->default = $default;
        $status->save();

        return $status;
    }

    #[Test]
    public function the_default_status_counts_as_ready_even_after_renaming(): void
    {
        $ready = $this->createStatus('Einsatzbereit', true);
        $broken = $this->createStatus('Defekt');
        $article = InventoryArticle::factory()->create(['quantity' => 10, 'is_detailed_quantity' => false]);
        $article->statusValues()->attach([$ready->id => ['value' => 7], $broken->id => ['value' => 3]]);

        $ready->update(['name' => 'Verfügbar']);

        $this->assertSame(7.0, $article->fresh()->readyQuantity());
        $this->assertSame(7.0, (float) $article->fresh()->getAvailableStock('2026-10-01', '2026-10-02')['total']);
    }

    #[Test]
    public function detailed_articles_sum_the_items_in_the_default_status(): void
    {
        $ready = $this->createStatus('Bereit', true);
        $broken = $this->createStatus('Defekt');
        $article = InventoryArticle::factory()->create(['quantity' => 3, 'is_detailed_quantity' => true]);
        foreach ([[$ready, 2], [$broken, 1]] as [$status, $quantity]) {
            InventoryDetailedQuantityArticle::factory()->create([
                'inventory_article_id' => $article->id,
                'inventory_article_status_id' => $status->id,
                'quantity' => $quantity,
            ]);
        }

        $this->assertSame(2.0, $article->fresh()->readyQuantity());
    }

    #[Test]
    public function articles_without_maintained_status_values_count_with_their_total_quantity(): void
    {
        $this->createStatus('Einsatzbereit', true);
        $article = InventoryArticle::factory()->create(['quantity' => 5, 'is_detailed_quantity' => false]);

        $this->assertSame(5.0, $article->fresh()->readyQuantity());
    }

    #[Test]
    public function the_update_command_no_longer_recreates_renamed_statuses(): void
    {
        $ready = $this->createStatus('Verfügbar', true);
        $ready->update(['order' => 7]);
        $duplicateDefault = $this->createStatus('Einsatzbereit', true);

        $command = app(\Artwork\Core\Console\Commands\UpdateArtwork::class);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            new \Symfony\Component\Console\Input\ArrayInput([]),
            new \Symfony\Component\Console\Output\NullOutput()
        ));
        (new \ReflectionMethod($command, 'addOrderInInventoryStatus'))->invoke($command);

        // Umbenannter Status behält Reihenfolge und Standard-Rolle, der Doppel-Standard verliert sie.
        $this->assertSame(7, (int) $ready->fresh()->order);
        $this->assertTrue($ready->fresh()->default);
        $this->assertFalse($duplicateDefault->fresh()->default);
        $this->assertSame($ready->id, InventoryArticleStatus::defaultStatusId());
    }
}
